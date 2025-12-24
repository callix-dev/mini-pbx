<?php

namespace App\Console\Commands;

use App\Events\AgentStatusChanged;
use App\Events\CallEnded;
use App\Events\CallStarted;
use App\Events\ExtensionStatusChanged;
use App\Events\QueueUpdated;
use App\Models\CallLog;
use App\Models\Extension;
use App\Models\Queue;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AmiListener extends Command
{
    protected $signature = 'ami:listen {--debug : Enable debug output}';
    protected $description = 'Listen to Asterisk AMI events and broadcast to WebSocket clients';

    private $socket;
    private $connected = false;
    private $activeCalls = [];
    private int $reconnectAttempts = 0;
    private const MAX_RECONNECT_ATTEMPTS = 10;
    private const RECONNECT_DELAY = 5; // seconds

    public function handle(): int
    {
        $this->info('Starting AMI Listener...');

        // Use config first, fall back to SystemSetting
        $host = config('asterisk.ami.host') ?: SystemSetting::get('ami_host', '127.0.0.1');
        $port = (int) (config('asterisk.ami.port') ?: SystemSetting::get('ami_port', 5038));
        $username = config('asterisk.ami.username') ?: SystemSetting::get('ami_username', 'admin');
        $secret = config('asterisk.ami.password') ?: SystemSetting::get('ami_secret', '');

        while ($this->reconnectAttempts < self::MAX_RECONNECT_ATTEMPTS) {
            try {
                $this->connect($host, $port);
                $this->login($username, $secret);

                $this->info('Connected to AMI. Listening for events...');
                $this->reconnectAttempts = 0; // Reset on successful connection

                while ($this->connected) {
                    $response = $this->readResponse();

                    if ($response) {
                        $this->processEvent($response);
                    }

                    usleep(10000); // 10ms delay to prevent CPU spinning
                }
            } catch (\Exception $e) {
                $this->error('AMI Error: ' . $e->getMessage());
                Log::error('AMI Listener Error', ['error' => $e->getMessage()]);
                
                $this->reconnectAttempts++;
                if ($this->reconnectAttempts < self::MAX_RECONNECT_ATTEMPTS) {
                    $this->warn("Reconnecting in " . self::RECONNECT_DELAY . " seconds... (attempt {$this->reconnectAttempts}/" . self::MAX_RECONNECT_ATTEMPTS . ")");
                    sleep(self::RECONNECT_DELAY);
                }
            }
        }

        $this->error('Max reconnect attempts reached. Exiting.');
        return 1;
    }

    private function connect(string $host, int $port): void
    {
        $this->socket = fsockopen($host, $port, $errno, $errstr, 30);

        if (!$this->socket) {
            throw new \RuntimeException("Failed to connect to AMI: $errstr ($errno)");
        }

        stream_set_timeout($this->socket, 1);
        $this->connected = true;

        // Read the welcome message
        fgets($this->socket);
    }

    private function login(string $username, string $secret): void
    {
        $this->sendCommand([
            'Action' => 'Login',
            'Username' => $username,
            'Secret' => $secret,
            'Events' => 'on',
        ]);

        $response = $this->readResponse();

        if (!$response || ($response['Response'] ?? '') !== 'Success') {
            throw new \RuntimeException('AMI Login failed: ' . ($response['Message'] ?? 'Unknown error'));
        }
    }

    private function sendCommand(array $command): void
    {
        $message = '';
        foreach ($command as $key => $value) {
            $message .= "$key: $value\r\n";
        }
        $message .= "\r\n";

        fwrite($this->socket, $message);
    }

    private function readResponse(): ?array
    {
        $response = [];
        $line = '';

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

    private function processEvent(array $event): void
    {
        $eventType = $event['Event'] ?? null;

        if (!$eventType) {
            return;
        }

        if ($this->option('debug')) {
            $this->line("Event: $eventType - " . json_encode($event));
        }

        match ($eventType) {
            'Newchannel' => $this->handleNewChannel($event),
            'Hangup' => $this->handleHangup($event),
            'Bridge' => $this->handleBridge($event),
            'Dial' => $this->handleDial($event),
            'AgentLogin', 'AgentLogoff' => $this->handleAgentLogin($event),
            'QueueMemberStatus' => $this->handleQueueMemberStatus($event),
            'QueueCallerJoin' => $this->handleQueueCallerJoin($event),
            'QueueCallerLeave' => $this->handleQueueCallerLeave($event),
            'DeviceStateChange' => $this->handleDeviceStateChange($event),
            // PJSIP Registration Events
            'ContactStatus' => $this->handleContactStatus($event),
            'PeerStatus' => $this->handlePeerStatus($event),
            'ContactStatusDetail' => $this->handleContactStatusDetail($event),
            default => null,
        };
    }

    private function handleNewChannel(array $event): void
    {
        $uniqueId = $event['Uniqueid'] ?? null;
        $channel = $event['Channel'] ?? null;
        $callerId = $event['CallerIDNum'] ?? null;
        $exten = $event['Exten'] ?? null;
        $context = $event['Context'] ?? null;

        if (!$uniqueId) {
            return;
        }

        // Determine call type
        $type = 'internal';
        if (str_starts_with($context ?? '', 'from-trunk')) {
            $type = 'inbound';
        } elseif (str_starts_with($context ?? '', 'from-internal')) {
            $type = 'outbound';
        }

        $this->activeCalls[$uniqueId] = [
            'unique_id' => $uniqueId,
            'channel' => $channel,
            'caller_id' => $callerId,
            'destination' => $exten,
            'type' => $type,
            'started_at' => now(),
        ];

        broadcast(new CallStarted($this->activeCalls[$uniqueId]));
    }

    private function handleHangup(array $event): void
    {
        $uniqueId = $event['Uniqueid'] ?? null;
        $cause = $event['Cause'] ?? 0;
        $causeTxt = $event['Cause-txt'] ?? '';

        if (!$uniqueId || !isset($this->activeCalls[$uniqueId])) {
            return;
        }

        $call = $this->activeCalls[$uniqueId];
        $duration = now()->diffInSeconds($call['started_at']);

        // Determine status
        $status = match ((int) $cause) {
            16, 17 => 'answered',
            19 => 'missed',
            21 => 'rejected',
            default => 'failed',
        };

        $callData = [
            'unique_id' => $uniqueId,
            'channel' => $call['channel'],
            'duration' => $duration,
            'status' => $status,
        ];

        broadcast(new CallEnded($callData));

        // Save call log
        try {
            CallLog::create([
                'unique_id' => $uniqueId,
                'source' => $call['caller_id'],
                'destination' => $call['destination'],
                'type' => $call['type'],
                'status' => $status,
                'duration' => $duration,
                'hangup_cause' => $cause,
                'started_at' => $call['started_at'],
                'ended_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save call log', ['error' => $e->getMessage()]);
        }

        unset($this->activeCalls[$uniqueId]);
    }

    private function handleBridge(array $event): void
    {
        // Handle call bridging events
    }

    private function handleDial(array $event): void
    {
        // Handle dial events
    }

    private function handleAgentLogin(array $event): void
    {
        $agent = $event['Agent'] ?? null;
        $eventType = $event['Event'] ?? '';

        if (!$agent) {
            return;
        }

        $user = User::whereHas('extension', function ($q) use ($agent) {
            $q->where('extension', $agent);
        })->first();

        if ($user) {
            $previousStatus = $user->agent_status;
            $newStatus = $eventType === 'AgentLogin' ? 'available' : 'offline';

            $user->setStatus($newStatus);

            broadcast(new AgentStatusChanged($user, $previousStatus, $newStatus));
        }
    }

    private function handleQueueMemberStatus(array $event): void
    {
        $queueName = $event['Queue'] ?? null;
        $interface = $event['Interface'] ?? null;
        $status = $event['Status'] ?? 0;
        $paused = ($event['Paused'] ?? '0') === '1';

        // Update queue member status in database
    }

    private function handleQueueCallerJoin(array $event): void
    {
        $queueName = $event['Queue'] ?? null;
        $position = $event['Position'] ?? 0;
        $count = $event['Count'] ?? 0;

        if (!$queueName) {
            return;
        }

        broadcast(new QueueUpdated([
            'queue_name' => $queueName,
            'waiting' => $count,
            'event' => 'caller_join',
        ]));
    }

    private function handleQueueCallerLeave(array $event): void
    {
        $queueName = $event['Queue'] ?? null;
        $count = $event['Count'] ?? 0;

        if (!$queueName) {
            return;
        }

        broadcast(new QueueUpdated([
            'queue_name' => $queueName,
            'waiting' => $count,
            'event' => 'caller_leave',
        ]));
    }

    private function handleDeviceStateChange(array $event): void
    {
        $device = $event['Device'] ?? null;
        $state = $event['State'] ?? null;

        // Update extension status based on device state
        if ($device && str_starts_with($device, 'PJSIP/')) {
            $extension = str_replace('PJSIP/', '', $device);

            $extensionModel = Extension::where('extension', $extension)->first();
            if ($extensionModel) {
                $previousStatus = $extensionModel->status;
                $status = match ($state) {
                    'NOT_INUSE' => 'online',
                    'INUSE', 'BUSY' => 'on_call',
                    'RINGING' => 'ringing',
                    default => 'offline',
                };

                if ($previousStatus !== $status) {
                    $extensionModel->update(['status' => $status]);
                    
                    // Broadcast extension status change
                    broadcast(new ExtensionStatusChanged($extensionModel, $previousStatus, $status));
                    
                    // Also broadcast agent status if user is assigned
                    if ($extensionModel->user) {
                        broadcast(new AgentStatusChanged($extensionModel->user, $previousStatus, $status));
                    }
                    
                    if ($this->option('debug')) {
                        $this->info("Extension {$extension} status changed from {$previousStatus} to {$status}");
                    }
                }
            }
        }
    }

    /**
     * Handle PJSIP ContactStatus event
     * 
     * Fired when a contact's reachability changes.
     * ContactStatus: Reachable, Unreachable, NonQualified, Removed, Updated, Created
     */
    private function handleContactStatus(array $event): void
    {
        $aor = $event['AOR'] ?? null; // Address of Record (typically the extension number)
        $contactStatus = $event['ContactStatus'] ?? null;
        $uri = $event['URI'] ?? null;
        $userAgent = $event['UserAgent'] ?? null;

        if (!$aor) {
            return;
        }

        // Extract extension number from AOR (format is usually "extension@realm" or just "extension")
        $extension = explode('@', $aor)[0];

        $extensionModel = Extension::where('extension', $extension)->first();
        if (!$extensionModel) {
            if ($this->option('debug')) {
                $this->warn("ContactStatus: Extension {$extension} not found in database");
            }
            return;
        }

        // Determine status based on ContactStatus
        $status = match ($contactStatus) {
            'Reachable', 'Created', 'Updated' => 'online',
            'Unreachable', 'Removed' => 'offline',
            'NonQualified' => 'offline', // Qualify not enabled or failed
            default => $extensionModel->status,
        };

        // Extract IP address from URI (format: sip:ext@ip:port)
        $ipAddress = null;
        if ($uri && preg_match('/@([^:]+)/', $uri, $matches)) {
            $ipAddress = $matches[1];
        }

        $previousStatus = $extensionModel->status;
        $updateData = ['status' => $status];
        
        // Only update registration info if becoming reachable
        if (in_array($contactStatus, ['Reachable', 'Created', 'Updated'])) {
            $updateData['last_registered_at'] = now();
            if ($ipAddress) {
                $updateData['last_registered_ip'] = $ipAddress;
            }
        }

        // Only broadcast if status actually changed
        if ($previousStatus !== $status) {
            $extensionModel->update($updateData);

            // Broadcast extension status change
            broadcast(new ExtensionStatusChanged($extensionModel, $previousStatus, $status));

            // Broadcast agent status change if user is assigned
            if ($extensionModel->user) {
                broadcast(new AgentStatusChanged($extensionModel->user, $previousStatus, $status));
            }

            if ($this->option('debug')) {
                $this->info("ContactStatus: Extension {$extension} changed from {$previousStatus} to {$status} from {$ipAddress}");
            }

            Log::info("PJSIP ContactStatus", [
                'extension' => $extension,
                'contact_status' => $contactStatus,
                'previous_status' => $previousStatus,
                'status' => $status,
                'ip' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        } else {
            // Still update registration info even if status didn't change
            $extensionModel->update($updateData);
        }
    }

    /**
     * Handle PJSIP PeerStatus event
     * 
     * Fired when an endpoint's registration status changes.
     * PeerStatus: Registered, Unregistered, Rejected, Reachable, Unreachable
     */
    private function handlePeerStatus(array $event): void
    {
        $peer = $event['Peer'] ?? null; // Format: PJSIP/extension
        $peerStatus = $event['PeerStatus'] ?? null;
        $address = $event['Address'] ?? null;
        $cause = $event['Cause'] ?? null;

        if (!$peer || !str_starts_with($peer, 'PJSIP/')) {
            return;
        }

        $extension = str_replace('PJSIP/', '', $peer);

        $extensionModel = Extension::where('extension', $extension)->first();
        if (!$extensionModel) {
            if ($this->option('debug')) {
                $this->warn("PeerStatus: Extension {$extension} not found in database");
            }
            return;
        }

        // Determine status based on PeerStatus
        $status = match ($peerStatus) {
            'Registered', 'Reachable' => 'online',
            'Unregistered', 'Unreachable' => 'offline',
            'Rejected' => 'offline',
            default => $extensionModel->status,
        };

        // Extract IP from address (format: ip:port)
        $ipAddress = null;
        if ($address && preg_match('/^([^:]+)/', $address, $matches)) {
            $ipAddress = $matches[1];
        }

        $previousStatus = $extensionModel->status;
        $updateData = ['status' => $status];
        
        // Only update registration info on successful registration
        if ($peerStatus === 'Registered') {
            $updateData['last_registered_at'] = now();
            if ($ipAddress) {
                $updateData['last_registered_ip'] = $ipAddress;
            }
        }

        // Only broadcast if status actually changed
        if ($previousStatus !== $status) {
            $extensionModel->update($updateData);

            // Broadcast extension status change
            broadcast(new ExtensionStatusChanged($extensionModel, $previousStatus, $status));

            // Broadcast agent status change if user is assigned
            if ($extensionModel->user) {
                broadcast(new AgentStatusChanged($extensionModel->user, $previousStatus, $status));
            }

            if ($this->option('debug')) {
                $this->info("PeerStatus: {$peer} changed from {$previousStatus} to {$status}" . ($cause ? " - {$cause}" : ''));
            }

            Log::info("PJSIP PeerStatus", [
                'extension' => $extension,
                'peer_status' => $peerStatus,
                'previous_status' => $previousStatus,
                'status' => $status,
                'ip' => $ipAddress,
                'cause' => $cause,
            ]);
        } else {
            // Still update registration info even if status didn't change
            $extensionModel->update($updateData);
        }
    }

    /**
     * Handle ContactStatusDetail event
     * 
     * Provides detailed information about a contact during a ContactList action.
     */
    private function handleContactStatusDetail(array $event): void
    {
        $aor = $event['AOR'] ?? null;
        $status = $event['Status'] ?? null;
        $uri = $event['URI'] ?? null;
        $userAgent = $event['UserAgent'] ?? null;
        $roundtripUsec = $event['RoundtripUsec'] ?? null;

        if (!$aor) {
            return;
        }

        $extension = explode('@', $aor)[0];

        // Extract IP address from URI
        $ipAddress = null;
        if ($uri && preg_match('/@([^:]+)/', $uri, $matches)) {
            $ipAddress = $matches[1];
        }

        $extensionModel = Extension::where('extension', $extension)->first();
        if ($extensionModel) {
            $previousStatus = $extensionModel->status;
            $isReachable = $status === 'Reachable';
            $newStatus = $isReachable ? 'online' : 'offline';
            
            $updateData = [
                'status' => $newStatus,
            ];
            
            if ($isReachable) {
                $updateData['last_registered_at'] = now();
                if ($ipAddress) {
                    $updateData['last_registered_ip'] = $ipAddress;
                }
            }

            if ($previousStatus !== $newStatus) {
                $extensionModel->update($updateData);
                
                // Broadcast status change
                broadcast(new ExtensionStatusChanged($extensionModel, $previousStatus, $newStatus));
                
                if ($extensionModel->user) {
                    broadcast(new AgentStatusChanged($extensionModel->user, $previousStatus, $newStatus));
                }

                if ($this->option('debug')) {
                    $latencyMs = $roundtripUsec ? round($roundtripUsec / 1000, 2) : 'N/A';
                    $this->info("ContactStatusDetail: {$extension} changed from {$previousStatus} to {$newStatus} (latency: {$latencyMs}ms)");
                }
            } else {
                $extensionModel->update($updateData);
            }
        }
    }

    /**
     * Request contact list from Asterisk to refresh all extension statuses
     */
    public function refreshContactStatus(): void
    {
        $this->sendCommand([
            'Action' => 'PJSIPShowContacts',
        ]);
    }

    public function __destruct()
    {
        if ($this->socket) {
            $this->sendCommand([
                'Action' => 'Logoff',
            ]);
            fclose($this->socket);
        }
    }
}
