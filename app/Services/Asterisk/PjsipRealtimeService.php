<?php

namespace App\Services\Asterisk;

use App\Models\Extension;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing PJSIP Realtime database entries
 * 
 * This service syncs Laravel Extension data to the ps_* tables
 * that Asterisk reads directly via the Realtime engine.
 */
class PjsipRealtimeService
{
    /**
     * Default transport for endpoints
     */
    protected string $defaultTransport;

    /**
     * Default context for incoming calls
     */
    protected string $defaultContext;

    /**
     * Allowed codecs
     */
    protected string $allowedCodecs;

    public function __construct()
    {
        $this->defaultTransport = config('asterisk.pjsip.default_transport', 'transport-udp');
        $this->defaultContext = config('asterisk.pjsip.default_context', 'from-internal');
        $this->allowedCodecs = config('asterisk.pjsip.allowed_codecs', 'ulaw,alaw,g722,opus');
    }

    /**
     * Sync an extension to PJSIP realtime tables
     */
    public function syncEndpoint(Extension $extension): bool
    {
        if (!$extension->is_active) {
            // If extension is inactive, remove from PJSIP
            return $this->deleteEndpoint($extension->extension);
        }

        try {
            DB::beginTransaction();

            // Sync to ps_endpoints
            $this->syncPsEndpoint($extension);

            // Sync to ps_auths
            $this->syncPsAuth($extension);

            // Sync to ps_aors
            $this->syncPsAor($extension);

            DB::commit();

            Log::info("PJSIP: Synced extension {$extension->extension}");
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("PJSIP: Failed to sync extension {$extension->extension}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete an endpoint from PJSIP realtime tables
     */
    public function deleteEndpoint(string $id): bool
    {
        try {
            DB::beginTransaction();

            // Delete from all PJSIP tables
            DB::table('ps_contacts')->where('endpoint', $id)->delete();
            DB::table('ps_aors')->where('id', $id)->delete();
            DB::table('ps_auths')->where('id', $id)->delete();
            DB::table('ps_endpoints')->where('id', $id)->delete();

            DB::commit();

            Log::info("PJSIP: Deleted endpoint {$id}");
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("PJSIP: Failed to delete endpoint {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sync all active extensions to PJSIP tables
     */
    public function syncAll(): array
    {
        $results = [
            'synced' => 0,
            'deleted' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        // Get all extension IDs currently in ps_endpoints
        $existingIds = DB::table('ps_endpoints')->pluck('id')->toArray();
        
        // Get all active extensions
        $extensions = Extension::active()->get();
        $activeIds = $extensions->pluck('extension')->toArray();

        // Sync each active extension
        foreach ($extensions as $extension) {
            if ($this->syncEndpoint($extension)) {
                $results['synced']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Failed to sync extension {$extension->extension}";
            }
        }

        // Delete endpoints that no longer exist in Laravel
        $orphanedIds = array_diff($existingIds, $activeIds);
        foreach ($orphanedIds as $id) {
            if ($this->deleteEndpoint($id)) {
                $results['deleted']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Failed to delete orphaned endpoint {$id}";
            }
        }

        return $results;
    }

    /**
     * Get registration status for an endpoint from ps_contacts
     */
    public function getRegistrationStatus(string $endpoint): ?array
    {
        $contact = DB::table('ps_contacts')
            ->where('endpoint', $endpoint)
            ->first();

        if (!$contact) {
            return null;
        }

        return [
            'registered' => true,
            'uri' => $contact->uri,
            'user_agent' => $contact->user_agent,
            'via_addr' => $contact->via_addr,
            'via_port' => $contact->via_port,
            'expiration_time' => $contact->expiration_time,
        ];
    }

    /**
     * Check if an endpoint is currently registered
     */
    public function isRegistered(string $endpoint): bool
    {
        return DB::table('ps_contacts')
            ->where('endpoint', $endpoint)
            ->exists();
    }

    /**
     * Get all registered endpoints
     */
    public function getRegisteredEndpoints(): array
    {
        return DB::table('ps_contacts')
            ->select('endpoint', 'uri', 'user_agent', 'via_addr', 'via_port')
            ->get()
            ->keyBy('endpoint')
            ->toArray();
    }

    /**
     * Sync endpoint data to ps_endpoints table
     */
    protected function syncPsEndpoint(Extension $extension): void
    {
        $callerId = $extension->caller_id_name 
            ? "\"{$extension->caller_id_name}\" <{$extension->caller_id_number}>"
            : "\"{$extension->name}\" <{$extension->extension}>";

        $mailbox = $extension->voicemail_enabled 
            ? "{$extension->extension}@default" 
            : null;

        // Build endpoint settings from extension settings
        $settings = $extension->settings ?? [];
        
        // Check if WebRTC is enabled (default to true for softphone users)
        $isWebRtc = $settings['webrtc'] ?? true;

        $data = [
            'id' => $extension->extension,
            // Use transport-ws for WebRTC, otherwise use default
            'transport' => $isWebRtc ? 'transport-ws' : ($settings['transport'] ?? $this->defaultTransport),
            'aors' => $extension->extension,
            'auth' => $extension->extension,
            'context' => $extension->context ?? $this->defaultContext,
            'disallow' => 'all',
            // WebRTC needs opus codec
            'allow' => $isWebRtc ? 'opus,ulaw,alaw,g722' : ($settings['codecs'] ?? $this->allowedCodecs),
            'direct_media' => 'no', // Must be 'no' for WebRTC
            'force_rport' => 'yes',
            'rewrite_contact' => 'yes',
            'rtp_symmetric' => 'yes',
            'callerid' => $callerId,
            'mailboxes' => $mailbox,
            'voicemail_extension' => $extension->voicemail_enabled ? '*97' : null,
            // WebRTC requires specific DTMF mode
            'dtmf_mode' => $isWebRtc ? 'rfc4733' : ($settings['dtmf_mode'] ?? 'rfc4733'),
            // WebRTC requirements
            'ice_support' => $isWebRtc ? 'yes' : 'no',
            'media_encryption' => $isWebRtc ? 'dtls' : 'no',
            'media_encryption_optimistic' => $isWebRtc ? 'yes' : 'no',
            'use_avpf' => $isWebRtc ? 'yes' : 'no',
            'webrtc' => $isWebRtc ? 'yes' : 'no',
            'language' => $settings['language'] ?? 'en',
            'allow_transfer' => 'yes',
            'allow_subscribe' => 'yes',
            'send_pai' => 'yes',
            'send_rpid' => 'yes',
            'send_diversion' => 'yes',
            'trust_id_inbound' => 'no',
            'trust_id_outbound' => 'no',
            'one_touch_recording' => $settings['one_touch_recording'] ?? false ? 'yes' : 'no',
            // Additional WebRTC settings
            'media_use_received_transport' => $isWebRtc ? 'yes' : 'no',
        ];

        // Handle call groups if set
        if (isset($settings['call_group'])) {
            $data['call_group'] = (string) $settings['call_group'];
        }
        if (isset($settings['pickup_group'])) {
            $data['pickup_group'] = (string) $settings['pickup_group'];
        }
        if (isset($settings['named_call_group'])) {
            $data['named_call_group'] = $settings['named_call_group'];
        }
        if (isset($settings['named_pickup_group'])) {
            $data['named_pickup_group'] = $settings['named_pickup_group'];
        }

        DB::table('ps_endpoints')->updateOrInsert(
            ['id' => $extension->extension],
            $data
        );
    }

    /**
     * Sync auth data to ps_auths table
     */
    protected function syncPsAuth(Extension $extension): void
    {
        $data = [
            'id' => $extension->extension,
            'auth_type' => 'userpass',
            'username' => $extension->extension,
            'password' => $extension->password, // Plain text for PJSIP
            'nonce_lifetime' => 32,
        ];

        DB::table('ps_auths')->updateOrInsert(
            ['id' => $extension->extension],
            $data
        );
    }

    /**
     * Sync AOR data to ps_aors table
     */
    protected function syncPsAor(Extension $extension): void
    {
        $settings = $extension->settings ?? [];

        $data = [
            'id' => $extension->extension,
            'max_contacts' => $settings['max_contacts'] ?? 1,
            'remove_existing' => 'yes',
            'minimum_expiration' => 60,
            'maximum_expiration' => 7200,
            'default_expiration' => $settings['registration_expiry'] ?? 3600,
            'qualify_frequency' => '60',
            'qualify_timeout' => 3.0,
            'authenticate_qualify' => 'no',
            'mailboxes' => $extension->voicemail_enabled 
                ? "{$extension->extension}@default" 
                : null,
            'voicemail_extension' => $extension->voicemail_enabled ? '*97' : null,
        ];

        DB::table('ps_aors')->updateOrInsert(
            ['id' => $extension->extension],
            $data
        );
    }

    /**
     * Create a WebRTC-enabled endpoint
     */
    public function createWebRtcEndpoint(Extension $extension): bool
    {
        // Set WebRTC settings on the extension
        $settings = $extension->settings ?? [];
        $settings['webrtc'] = true;
        $settings['transport'] = 'transport-wss';
        $settings['codecs'] = 'opus,ulaw,alaw';
        $extension->settings = $settings;
        $extension->save();

        return $this->syncEndpoint($extension);
    }

    /**
     * Get endpoint statistics
     */
    public function getStats(): array
    {
        return [
            'total_endpoints' => DB::table('ps_endpoints')->count(),
            'total_registered' => DB::table('ps_contacts')->distinct('endpoint')->count(),
            'endpoints_by_transport' => DB::table('ps_endpoints')
                ->select('transport', DB::raw('count(*) as count'))
                ->groupBy('transport')
                ->pluck('count', 'transport')
                ->toArray(),
        ];
    }
}

