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
use App\Models\User;
use App\Services\SettingsService;
use Carbon\Carbon;
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

        // Use SettingsService for hybrid DB/ENV settings
        $settings = SettingsService::getAmiSettings();
        $host = $settings['host'];
        $port = $settings['port'];
        $username = $settings['username'];
        $secret = $settings['password'];

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
            // Dial events for ringing status
            'DialBegin' => $this->handleDial($event),
            'DialEnd' => $this->handleDial($event),
            // CDR Event - Most reliable for call logging
            'Cdr' => $this->handleCdr($event),
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
            // from-internal means an extension is calling out
            $type = 'internal'; // Could be internal or outbound depending on destination
        }

        $this->activeCalls[$uniqueId] = [
            'unique_id' => $uniqueId,
            'channel' => $channel,
            'caller_id' => $callerId,
            'destination' => $exten,
            'type' => $type,
            'started_at' => now(),
        ];

        // Update extension status to on_call
        $extension = Extension::where('extension', $callerId)->first();
        if ($extension && $extension->status !== 'on_call') {
            $extension->status = 'on_call';
            $extension->saveQuietly();
            
            // Broadcast status change
            broadcast(new ExtensionStatusChanged($extension->id, $extension->extension, 'online', 'on_call'));
        }

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
            // Find extension_id from caller
            $extension = Extension::where('extension', $call['caller_id'])->first();
            $calleeExtension = Extension::where('extension', $call['destination'])->first();

            CallLog::create([
                'uniqueid' => $uniqueId,
                'linkedid' => $uniqueId,
                'type' => $call['type'],
                'direction' => $call['type'] === 'inbound' ? 'inbound' : 'outbound',
                'caller_id' => $call['caller_id'],
                'caller_name' => $extension?->name ?? $call['caller_id'],
                'callee_id' => $call['destination'],
                'callee_name' => $calleeExtension?->name ?? $call['destination'],
                'extension_id' => $extension?->id,
                'status' => $status,
                'start_time' => $call['started_at'],
                'answer_time' => $status === 'answered' ? $call['started_at'] : null,
                'end_time' => now(),
                'duration' => $duration,
                'billable_duration' => $status === 'answered' ? $duration : 0,
                'hangup_cause' => $cause,
            ]);
            
            if ($this->option('debug')) {
                $this->info("Call logged: {$call['caller_id']} -> {$call['destination']} ({$status}, {$duration}s)");
            }

            // Reset extension status to online (they're still registered)
            if ($extension && $extension->status === 'on_call') {
                $extension->status = 'online';
                $extension->saveQuietly();
                broadcast(new ExtensionStatusChanged($extension->id, $extension->extension, 'on_call', 'online'));
            }
            if ($calleeExtension && $calleeExtension->status === 'on_call') {
                $calleeExtension->status = 'online';
                $calleeExtension->saveQuietly();
                broadcast(new ExtensionStatusChanged($calleeExtension->id, $calleeExtension->extension, 'on_call', 'online'));
            }

        } catch (\Exception $e) {
            Log::error('Failed to save call log', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }

        unset($this->activeCalls[$uniqueId]);
    }

    /**
     * Handle CDR event from Asterisk
     * This is the most reliable way to capture call records
     * as Asterisk fires this at the end of every call with complete data
     */
    private function handleCdr(array $event): void
    {
        $uniqueId = $event['UniqueID'] ?? $event['Uniqueid'] ?? null;
        $linkedId = $event['LinkedID'] ?? $event['Linkedid'] ?? $uniqueId;
        $source = $event['Source'] ?? '';
        $destination = $event['Destination'] ?? '';
        $dcontext = $event['DestinationContext'] ?? $event['Dcontext'] ?? '';
        $channel = $event['Channel'] ?? '';
        $destChannel = $event['DestinationChannel'] ?? '';
        $disposition = $event['Disposition'] ?? 'NO ANSWER';
        $duration = (int) ($event['Duration'] ?? 0);
        $billSec = (int) ($event['BillableSeconds'] ?? $event['Billsec'] ?? 0);
        $startTime = $event['StartTime'] ?? null;
        $answerTime = $event['AnswerTime'] ?? null;
        $endTime = $event['EndTime'] ?? null;

        if (!$uniqueId || !$source) {
            return;
        }

        if ($this->option('debug')) {
            $this->info("CDR: {$source} -> {$destination} ({$disposition}, {$duration}s)");
        }

        // Determine call type
        $type = 'internal';
        if (str_contains($dcontext, 'trunk') || str_contains($dcontext, 'external') || str_contains($dcontext, 'pstn')) {
            $type = str_contains($channel, 'PJSIP/') && !str_contains($channel, 'trunk') ? 'outbound' : 'inbound';
        } elseif (str_contains($channel, 'PJSIP/') && str_contains($destChannel, 'PJSIP/')) {
            $type = 'internal';
        }

        // Map Asterisk disposition to our status
        $status = match (strtoupper($disposition)) {
            'ANSWERED' => 'answered',
            'BUSY' => 'busy',
            'NO ANSWER', 'NOANSWER' => 'missed',
            'FAILED', 'CONGESTION' => 'failed',
            default => 'missed',
        };

        // Parse start/end times
        $parsedStartTime = $startTime ? Carbon::parse($startTime) : now()->subSeconds($duration);
        $parsedAnswerTime = $answerTime && $answerTime !== '' ? Carbon::parse($answerTime) : null;
        $parsedEndTime = $endTime ? Carbon::parse($endTime) : now();

        // Find extensions
        $sourceExt = Extension::where('extension', $source)->first();
        $destExt = Extension::where('extension', $destination)->first();

        try {
            // Check if CDR already exists (prevent duplicates)
            $existing = CallLog::where('uniqueid', $uniqueId)->first();
            if ($existing) {
                if ($this->option('debug')) {
                    $this->warn("CDR already exists for {$uniqueId}");
                }
                return;
            }

            CallLog::create([
                'uniqueid' => $uniqueId,
                'linkedid' => $linkedId,
                'type' => $type,
                'direction' => $type === 'inbound' ? 'inbound' : ($type === 'outbound' ? 'outbound' : 'internal'),
                'caller_id' => $source,
                'caller_name' => $sourceExt?->name ?? $source,
                'callee_id' => $destination,
                'callee_name' => $destExt?->name ?? $destination,
                'extension_id' => $sourceExt?->id ?? $destExt?->id,
                'status' => $status,
                'start_time' => $parsedStartTime,
                'answer_time' => $parsedAnswerTime,
                'end_time' => $parsedEndTime,
                'duration' => $duration,
                'billable_duration' => $billSec,
                'hangup_cause' => $disposition,
            ]);

            $this->info("CDR Logged: {$source} -> {$destination} ({$status}, {$duration}s)");

        } catch (\Exception $e) {
            Log::error('Failed to save CDR', [
                'uniqueid' => $uniqueId,
                'source' => $source,
                'destination' => $destination,
                'error' => $e->getMessage()
            ]);
            $this->error("CDR Save Error: " . $e->getMessage());
        }
    }

    private function handleBridge(array $event): void
    {
        // Handle call bridging events
    }

    private function handleDial(array $event): void
    {
        $subEvent = $event['SubEvent'] ?? $event['DialStatus'] ?? null;
        $destChannel = $event['DestChannel'] ?? $event['Destination'] ?? null;
        
        if (!$destChannel) {
            return;
        }

        // Extract extension from channel (e.g., "PJSIP/1001-00000005" -> "1001")
        if (preg_match('/PJSIP\/(\d+)/', $destChannel, $matches)) {
            $destExtension = $matches[1];
            $extension = Extension::where('extension', $destExtension)->first();
            
            if ($extension) {
                if ($subEvent === 'Begin' || $event['Event'] === 'DialBegin') {
                    // Set ringing status
                    if ($extension->status !== 'on_call') {
                        $previousStatus = $extension->status;
                        $extension->status = 'ringing';
                        $extension->saveQuietly();
                        broadcast(new ExtensionStatusChanged($extension->id, $extension->extension, $previousStatus, 'ringing'));
                    }
                } elseif ($subEvent === 'End' || $event['Event'] === 'DialEnd') {
                    $dialStatus = $event['DialStatus'] ?? '';
                    
                    if ($dialStatus === 'ANSWER') {
                        // Call answered - set to on_call
                        $extension->status = 'on_call';
                        $extension->saveQuietly();
                        broadcast(new ExtensionStatusChanged($extension->id, $extension->extension, 'ringing', 'on_call'));
                    } else {
                        // Call not answered - reset to online
                        $extension->status = 'online';
                        $extension->saveQuietly();
                        broadcast(new ExtensionStatusChanged($extension->id, $extension->extension, 'ringing', 'online'));
                    }
                }
            }
        }
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
