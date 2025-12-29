<?php

namespace App\Services\Asterisk;

use App\Models\Carrier;
use App\Services\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PjsipCarrierSyncService
{
    /**
     * Sync a carrier to Asterisk PJSIP tables
     */
    public function syncCarrier(Carrier $carrier, bool $reloadPjsip = true): void
    {
        $endpointId = $carrier->getPjsipEndpointName();

        try {
            DB::beginTransaction();

            if ($carrier->usesRegistration()) {
                $this->syncRegistrationCarrier($carrier, $endpointId);
            } else {
                $this->syncIpCarrier($carrier, $endpointId);
            }

            DB::commit();

            Log::info('Carrier synced to PJSIP', [
                'carrier_id' => $carrier->id,
                'endpoint_id' => $endpointId,
                'auth_type' => $carrier->auth_type,
            ]);

            // Reload PJSIP in Asterisk to load the new configuration
            if ($reloadPjsip) {
                $this->reloadPjsip();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to sync carrier to PJSIP', [
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reload PJSIP module in Asterisk via AMI
     */
    public function reloadPjsip(): bool
    {
        try {
            $settings = SettingsService::getAmiSettings();
            
            $socket = @fsockopen($settings['host'], $settings['port'], $errno, $errstr, 5);
            if (!$socket) {
                Log::warning('Could not connect to AMI for PJSIP reload', ['error' => $errstr]);
                return false;
            }

            stream_set_timeout($socket, 5);
            fgets($socket); // Read welcome

            // Login
            fwrite($socket, "Action: Login\r\nUsername: {$settings['username']}\r\nSecret: {$settings['password']}\r\n\r\n");
            $this->readAmiResponse($socket);

            // Reload PJSIP
            fwrite($socket, "Action: Command\r\nCommand: pjsip reload\r\n\r\n");
            $response = $this->readAmiResponse($socket);

            // Logoff
            fwrite($socket, "Action: Logoff\r\n\r\n");
            fclose($socket);

            Log::info('PJSIP reloaded via AMI');
            return true;
        } catch (\Exception $e) {
            Log::warning('Failed to reload PJSIP via AMI', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Read AMI response
     */
    private function readAmiResponse($socket): array
    {
        $response = [];
        while (($line = fgets($socket)) !== false) {
            $line = trim($line);
            if ($line === '') {
                if (!empty($response)) {
                    return $response;
                }
                continue;
            }
            if (strpos($line, ': ') !== false) {
                [$key, $value] = explode(': ', $line, 2);
                $response[$key] = $value;
            }
        }
        return $response;
    }

    /**
     * Sync a registration-based carrier (username/password)
     */
    private function syncRegistrationCarrier(Carrier $carrier, string $endpointId): void
    {
        // Default codecs if not specified
        $codecs = $carrier->codecs ?: Carrier::DEFAULT_CODECS;
        $codecsString = is_array($codecs) ? implode(',', $codecs) : $codecs;

        // Create/update endpoint
        $this->upsertEndpoint($endpointId, [
            'transport' => 'transport-' . $carrier->transport,
            'aors' => $endpointId,
            'auth' => $endpointId,
            'context' => $carrier->context,
            'disallow' => 'all',
            'allow' => $codecsString,
            'direct_media' => 'no',
            'force_rport' => 'yes',
            'rewrite_contact' => 'yes',
            'rtp_symmetric' => 'yes',
            'from_domain' => $carrier->from_domain ?: $carrier->host,
            'from_user' => $carrier->from_user ?: $carrier->username,
            'dtmf_mode' => 'rfc4733',
        ]);

        // Create/update auth
        $this->upsertAuth($endpointId, [
            'auth_type' => 'userpass',
            'username' => $carrier->username,
            'password' => $carrier->password,
        ]);

        // Create/update AOR
        $this->upsertAor($endpointId, [
            'contact' => $this->buildContactUri($carrier, $endpointId),
            'qualify_frequency' => 60,
            'max_contacts' => 1,
        ]);

        // Create/update registration (for outbound trunks)
        if ($carrier->type === 'outbound') {
            $this->upsertRegistration($endpointId, $carrier);
        }
    }

    /**
     * Sync an IP-based carrier
     */
    private function syncIpCarrier(Carrier $carrier, string $endpointId): void
    {
        // Default codecs if not specified
        $codecs = $carrier->codecs ?: Carrier::DEFAULT_CODECS;
        $codecsString = is_array($codecs) ? implode(',', $codecs) : $codecs;

        // Create/update endpoint (no auth for IP-based)
        $this->upsertEndpoint($endpointId, [
            'transport' => 'transport-' . $carrier->transport,
            'aors' => $endpointId,
            'context' => $carrier->context,
            'disallow' => 'all',
            'allow' => $codecsString,
            'direct_media' => 'no',
            'force_rport' => 'yes',
            'rewrite_contact' => 'yes',
            'rtp_symmetric' => 'yes',
            'from_domain' => $carrier->from_domain ?: $carrier->host,
            'dtmf_mode' => 'rfc4733',
        ]);

        // Create/update AOR
        $this->upsertAor($endpointId, [
            'contact' => $this->buildContactUri($carrier, $endpointId),
            'qualify_frequency' => 60,
            'max_contacts' => 1,
        ]);

        // Create/update identify for IP matching (inbound)
        if ($carrier->type === 'inbound') {
            $this->upsertIdentify($endpointId, $carrier->host);
        }
    }

    /**
     * Delete carrier from PJSIP tables
     */
    public function deleteCarrier(Carrier $carrier, bool $reloadPjsip = true): void
    {
        $endpointId = $carrier->getPjsipEndpointName();

        try {
            DB::beginTransaction();

            // Delete in reverse order of dependencies
            DB::table('ps_registrations')->where('id', $endpointId)->delete();
            DB::table('ps_endpoint_id_ips')->where('id', $endpointId)->delete();
            
            // ps_contacts may have different column names depending on schema
            if (\Schema::hasColumn('ps_contacts', 'aor')) {
                DB::table('ps_contacts')->where('aor', $endpointId)->delete();
            } elseif (\Schema::hasColumn('ps_contacts', 'endpoint')) {
                DB::table('ps_contacts')->where('endpoint', $endpointId)->delete();
            }
            
            DB::table('ps_aors')->where('id', $endpointId)->delete();
            DB::table('ps_auths')->where('id', $endpointId)->delete();
            DB::table('ps_endpoints')->where('id', $endpointId)->delete();

            DB::commit();

            Log::info('Carrier deleted from PJSIP', [
                'carrier_id' => $carrier->id,
                'endpoint_id' => $endpointId,
            ]);

            // Reload PJSIP in Asterisk
            if ($reloadPjsip) {
                $this->reloadPjsip();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete carrier from PJSIP', [
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Upsert endpoint
     */
    private function upsertEndpoint(string $id, array $data): void
    {
        $exists = DB::table('ps_endpoints')->where('id', $id)->exists();

        $fullData = array_merge(['id' => $id], $data);

        if ($exists) {
            DB::table('ps_endpoints')->where('id', $id)->update($data);
        } else {
            DB::table('ps_endpoints')->insert($fullData);
        }
    }

    /**
     * Upsert auth
     */
    private function upsertAuth(string $id, array $data): void
    {
        $exists = DB::table('ps_auths')->where('id', $id)->exists();

        $fullData = array_merge(['id' => $id], $data);

        if ($exists) {
            DB::table('ps_auths')->where('id', $id)->update($data);
        } else {
            DB::table('ps_auths')->insert($fullData);
        }
    }

    /**
     * Upsert AOR
     */
    private function upsertAor(string $id, array $data): void
    {
        $exists = DB::table('ps_aors')->where('id', $id)->exists();

        $fullData = array_merge(['id' => $id], $data);

        if ($exists) {
            DB::table('ps_aors')->where('id', $id)->update($data);
        } else {
            DB::table('ps_aors')->insert($fullData);
        }
    }

    /**
     * Upsert registration
     */
    private function upsertRegistration(string $id, Carrier $carrier): void
    {
        $exists = DB::table('ps_registrations')->where('id', $id)->exists();

        // Build server URI
        $serverUri = 'sip:' . $carrier->host;
        if ($carrier->port && $carrier->port != 5060) {
            $serverUri .= ':' . $carrier->port;
        }

        // Build client URI
        $clientUri = 'sip:' . ($carrier->username ?: $id) . '@' . $carrier->host;

        $data = [
            'server_uri' => $serverUri,
            'client_uri' => $clientUri,
            'outbound_auth' => $id,
            'transport' => 'transport-' . $carrier->transport,
            'retry_interval' => 60,
            'forbidden_retry_interval' => 300,
            'expiration' => 3600,
            'max_retries' => 10,
            'auth_rejection_permanent' => 'no',
        ];

        // Add outbound proxy if configured
        $outboundProxy = $carrier->getOutboundProxy();
        if ($outboundProxy) {
            $data['outbound_proxy'] = 'sip:' . $outboundProxy;
        }

        if ($exists) {
            DB::table('ps_registrations')->where('id', $id)->update($data);
        } else {
            DB::table('ps_registrations')->insert(array_merge(['id' => $id], $data));
        }
    }

    /**
     * Upsert identify (for IP matching)
     */
    private function upsertIdentify(string $id, string $host): void
    {
        $exists = DB::table('ps_endpoint_id_ips')->where('id', $id)->exists();

        $data = [
            'endpoint' => $id,
            'match' => $host,
        ];

        if ($exists) {
            DB::table('ps_endpoint_id_ips')->where('id', $id)->update($data);
        } else {
            DB::table('ps_endpoint_id_ips')->insert(array_merge(['id' => $id], $data));
        }
    }

    /**
     * Build contact URI for AOR
     */
    private function buildContactUri(Carrier $carrier, string $endpointId): string
    {
        $uri = 'sip:' . $endpointId . '@' . $carrier->host;
        if ($carrier->port && $carrier->port != 5060) {
            $uri .= ':' . $carrier->port;
        }
        return $uri;
    }

    /**
     * Check if carrier is synced
     */
    public function isCarrierSynced(Carrier $carrier): bool
    {
        $endpointId = $carrier->getPjsipEndpointName();
        return DB::table('ps_endpoints')->where('id', $endpointId)->exists();
    }

    /**
     * Sync all active carriers
     */
    public function syncAllCarriers(): void
    {
        $carriers = Carrier::active()->get();

        foreach ($carriers as $carrier) {
            try {
                // Don't reload after each carrier, we'll reload once at the end
                $this->syncCarrier($carrier, reloadPjsip: false);
            } catch (\Exception $e) {
                Log::error('Failed to sync carrier', [
                    'carrier_id' => $carrier->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Reload PJSIP once after all carriers are synced
        if ($carriers->isNotEmpty()) {
            $this->reloadPjsip();
        }
    }
}

