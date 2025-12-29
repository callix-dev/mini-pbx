<?php

namespace App\Services\Carrier;

use App\Models\Carrier;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Log;

class CarrierTestService
{
    private $socket;

    /**
     * Test carrier connection
     * 
     * @return array{success: bool, message: string, details: array}
     */
    public function testConnection(Carrier $carrier): array
    {
        $endpointId = $carrier->getPjsipEndpointName();

        try {
            $this->connect();
            $this->login();

            $results = [];

            // Test 1: Check if endpoint exists
            $endpointCheck = $this->checkEndpoint($endpointId);
            $results['endpoint'] = $endpointCheck;

            if (!$endpointCheck['exists']) {
                return [
                    'success' => false,
                    'message' => 'Endpoint not found in Asterisk. Please sync the carrier first.',
                    'details' => $results,
                ];
            }

            // Test 2: Check AOR/Contact
            $aorCheck = $this->checkAor($endpointId);
            $results['aor'] = $aorCheck;

            // Test 3: For registration-based, check registration status
            if ($carrier->usesRegistration() && $carrier->type === 'outbound') {
                $registrationCheck = $this->checkRegistration($endpointId);
                $results['registration'] = $registrationCheck;
            }

            // Test 4: Send SIP OPTIONS (qualify)
            $qualifyCheck = $this->sendQualify($endpointId);
            $results['qualify'] = $qualifyCheck;

            $this->disconnect();

            // Determine overall success
            $endpointExists = $endpointCheck['exists'] ?? false;
            $aorExists = $aorCheck['exists'] ?? false;
            $isReachable = $qualifyCheck['reachable'] ?? false;
            $isRegistered = $results['registration']['registered'] ?? null;

            // For outbound registration trunks, registration status is important
            if ($carrier->usesRegistration() && $carrier->type === 'outbound') {
                $success = $endpointExists && $aorExists && $isRegistered;
                
                if ($endpointExists && $aorExists && !$isRegistered) {
                    $message = 'Endpoint configured but not registered with provider. Check credentials.';
                } elseif ($success && !$isReachable) {
                    $message = 'Registered successfully but provider not responding to OPTIONS.';
                    $success = true; // Still consider success if registered
                } elseif ($success) {
                    $message = 'Connection test successful! Carrier is registered and reachable.';
                } else {
                    $message = 'Connection test failed. Check details for more information.';
                }
            } else {
                // For IP auth or inbound trunks
                $success = $endpointExists && $aorExists;
                $message = $success 
                    ? ($isReachable ? 'Connection test successful!' : 'Endpoint configured. Reachability depends on provider.')
                    : 'Connection test failed. Check details for more information.';
            }

            return [
                'success' => $success,
                'message' => $message,
                'details' => $results,
            ];
        } catch (\Exception $e) {
            Log::error('Carrier connection test failed', [
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage(),
                'details' => [],
            ];
        }
    }

    /**
     * Check if endpoint exists in Asterisk
     */
    private function checkEndpoint(string $endpointId): array
    {
        $this->sendCommand([
            'Action' => 'PJSIPShowEndpoint',
            'Endpoint' => $endpointId,
        ]);

        // Read response - may be Success or Error
        $response = $this->readResponse();
        $responseType = $response['Response'] ?? '';
        
        // Some AMI versions return different responses
        $exists = $responseType === 'Success' || 
                  (isset($response['Event']) && $response['Event'] === 'EndpointDetail');

        $deviceState = null;
        $endpointDetails = [];

        // Read additional data events until EndpointDetailComplete
        $maxReads = 50; // Prevent infinite loop
        $reads = 0;
        while ($reads < $maxReads) {
            $event = $this->readResponse();
            $reads++;
            
            if (!$event) break;
            
            $eventType = $event['Event'] ?? '';
            
            // EndpointDetail event contains the endpoint info
            if ($eventType === 'EndpointDetail') {
                $exists = true;
                $deviceState = $event['DeviceState'] ?? null;
                $endpointDetails = $event;
            }
            
            if ($eventType === 'EndpointDetailComplete') {
                break;
            }
        }

        return [
            'exists' => $exists,
            'device_state' => $deviceState,
            'message' => $exists 
                ? 'Endpoint found' . ($deviceState ? " (State: {$deviceState})" : '')
                : 'Endpoint not found',
        ];
    }

    /**
     * Check AOR status
     */
    private function checkAor(string $aorId): array
    {
        $this->sendCommand([
            'Action' => 'PJSIPShowAors',
            'ActionID' => 'aor-check-' . time(),
        ]);

        $found = false;
        $contacts = 0;

        while (true) {
            $response = $this->readResponse();
            if (!$response) break;

            if (($response['Event'] ?? '') === 'AorList') {
                if (($response['ObjectName'] ?? '') === $aorId) {
                    $found = true;
                    $contacts = (int) ($response['Contacts'] ?? 0);
                }
            }

            if (($response['Event'] ?? '') === 'AorListComplete') {
                break;
            }
        }

        return [
            'exists' => $found,
            'contacts' => $contacts,
            'message' => $found ? "AOR found with {$contacts} contact(s)" : 'AOR not found',
        ];
    }

    /**
     * Check registration status (for outbound trunks)
     */
    private function checkRegistration(string $registrationId): array
    {
        $this->sendCommand([
            'Action' => 'PJSIPShowRegistrationsOutbound',
        ]);

        $found = false;
        $status = 'Unknown';

        while (true) {
            $response = $this->readResponse();
            if (!$response) break;

            if (($response['Event'] ?? '') === 'OutboundRegistrationDetail') {
                if (($response['ObjectName'] ?? '') === $registrationId) {
                    $found = true;
                    $status = $response['Status'] ?? 'Unknown';
                }
            }

            if (($response['Event'] ?? '') === 'OutboundRegistrationDetailComplete') {
                break;
            }
        }

        $registered = stripos($status, 'Registered') !== false;

        return [
            'found' => $found,
            'registered' => $registered,
            'status' => $status,
            'message' => $found 
                ? ($registered ? 'Registered successfully' : "Registration status: {$status}") 
                : 'Registration not found',
        ];
    }

    /**
     * Send SIP OPTIONS (qualify) to test reachability
     */
    private function sendQualify(string $endpointId): array
    {
        $this->sendCommand([
            'Action' => 'PJSIPQualify',
            'Endpoint' => $endpointId,
        ]);

        $response = $this->readResponse();
        $success = ($response['Response'] ?? '') === 'Success';

        // Wait a moment for qualify to complete
        if ($success) {
            usleep(500000); // 500ms

            // Check contact status
            $this->sendCommand([
                'Action' => 'PJSIPShowContacts',
            ]);

            $reachable = false;
            $rtt = null;

            while (true) {
                $event = $this->readResponse();
                if (!$event) break;

                if (($event['Event'] ?? '') === 'ContactList') {
                    if (str_starts_with($event['Aor'] ?? '', $endpointId)) {
                        $status = $event['Status'] ?? '';
                        $reachable = stripos($status, 'Reachable') !== false || 
                                     stripos($status, 'Created') !== false;
                        $rtt = $event['RoundtripUsec'] ?? null;
                        if ($rtt) {
                            $rtt = round((int)$rtt / 1000, 2) . 'ms';
                        }
                    }
                }

                if (($event['Event'] ?? '') === 'ContactListComplete') {
                    break;
                }
            }

            return [
                'sent' => true,
                'reachable' => $reachable,
                'rtt' => $rtt,
                'message' => $reachable 
                    ? "Reachable" . ($rtt ? " (RTT: {$rtt})" : '')
                    : 'Not reachable or no response',
            ];
        }

        return [
            'sent' => false,
            'reachable' => false,
            'message' => 'Failed to send qualify request',
        ];
    }

    /**
     * Connect to AMI
     */
    private function connect(): void
    {
        $settings = SettingsService::getAmiSettings();

        $this->socket = fsockopen($settings['host'], $settings['port'], $errno, $errstr, 5);

        if (!$this->socket) {
            throw new \RuntimeException("Failed to connect to AMI: $errstr ($errno)");
        }

        stream_set_timeout($this->socket, 5);

        // Read welcome message
        fgets($this->socket);
    }

    /**
     * Login to AMI
     */
    private function login(): void
    {
        $settings = SettingsService::getAmiSettings();

        $this->sendCommand([
            'Action' => 'Login',
            'Username' => $settings['username'],
            'Secret' => $settings['password'],
        ]);

        $response = $this->readResponse();

        if (($response['Response'] ?? '') !== 'Success') {
            throw new \RuntimeException('AMI Login failed: ' . ($response['Message'] ?? 'Unknown error'));
        }
    }

    /**
     * Disconnect from AMI
     */
    private function disconnect(): void
    {
        if ($this->socket) {
            $this->sendCommand(['Action' => 'Logoff']);
            fclose($this->socket);
            $this->socket = null;
        }
    }

    /**
     * Send AMI command
     */
    private function sendCommand(array $command): void
    {
        $message = '';
        foreach ($command as $key => $value) {
            $message .= "$key: $value\r\n";
        }
        $message .= "\r\n";

        fwrite($this->socket, $message);
    }

    /**
     * Read AMI response
     */
    private function readResponse(): ?array
    {
        $response = [];

        while (($line = fgets($this->socket)) !== false) {
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

        return empty($response) ? null : $response;
    }
}

