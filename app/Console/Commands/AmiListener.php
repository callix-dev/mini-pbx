<?php

namespace App\Console\Commands;

use App\Events\AgentStatusChanged;
use App\Events\CallEnded;
use App\Events\CallStarted;
use App\Events\ExtensionStatusChanged;
use App\Events\QueueUpdated;
use App\Models\CallLog;
use App\Models\Extension;
use App\Models\ExtensionRegistration;
use App\Models\Queue;
use App\Models\User;
use App\Services\SettingsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
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

        // Skip internal AppDial channels (they have exten = 's' or empty)
        // These are secondary channels created when Dial() is called
        if (empty($exten) || $exten === 's' || $exten === 'h') {
            if ($this->option('debug')) {
                $this->line("Skipping internal channel: {$channel} (exten={$exten})");
            }
            return;
        }

        // Skip if caller is same as destination (invalid)
        if ($callerId === $exten) {
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
            broadcast(new ExtensionStatusChanged($extension, 'online', 'on_call'));
        }

        // Cache active call for dashboard
        $this->cacheActiveCall($uniqueId, $this->activeCalls[$uniqueId]);

        broadcast(new CallStarted($this->activeCalls[$uniqueId]));
    }

    /**
     * Cache active call for dashboard polling
     */
    private function cacheActiveCall(string $uniqueId, array $callData): void
    {
        $activeCalls = Cache::get('active_calls', []);
        $activeCalls[$uniqueId] = array_merge($callData, [
            'cached_at' => now()->toIso8601String(),
        ]);
        Cache::put('active_calls', $activeCalls, now()->addHours(2)); // Expire after 2 hours
    }

    /**
     * Remove call from cache when ended
     */
    private function uncacheActiveCall(string $uniqueId): void
    {
        $activeCalls = Cache::get('active_calls', []);
        unset($activeCalls[$uniqueId]);
        Cache::put('active_calls', $activeCalls, now()->addHours(2));
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
                broadcast(new ExtensionStatusChanged($extension, 'on_call', 'online'));
            }
            if ($calleeExtension && $calleeExtension->status === 'on_call') {
                $calleeExtension->status = 'online';
                $calleeExtension->saveQuietly();
                broadcast(new ExtensionStatusChanged($calleeExtension, 'on_call', 'online'));
            }

        } catch (\Exception $e) {
            Log::error('Failed to save call log', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }

        // Remove from cache
        $this->uncacheActiveCall($uniqueId);
        
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
        
        // Recording path - can be in UserField, AccountCode, or constructed from UniqueID
        $userField = $event['UserField'] ?? '';
        $accountCode = $event['AccountCode'] ?? '';
        $recordingPath = $this->resolveRecordingPath($uniqueId, $userField, $accountCode);

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

            $callLog = CallLog::create([
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
                'recording_path' => $recordingPath,
            ]);

            $recordingInfo = $recordingPath ? " [Recording: {$recordingPath}]" : '';
            $this->info("CDR Logged: {$source} -> {$destination} ({$status}, {$duration}s){$recordingInfo}");

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

    /**
     * Resolve recording path from CDR event data
     * Asterisk can store recording path in UserField or we construct it from UniqueID
     */
    private function resolveRecordingPath(?string $uniqueId, string $userField, string $accountCode): ?string
    {
        // Get configured recording directory
        $recordingDir = config('asterisk.recordings.path', '/var/spool/asterisk/monitor');
        
        // 1. Check UserField first (MixMonitor typically sets this)
        if (!empty($userField)) {
            // UserField might contain just filename or full path
            if (str_starts_with($userField, '/')) {
                // Full path provided
                if (file_exists($userField)) {
                    return $userField;
                }
            } else {
                // Just filename, construct full path
                $fullPath = "{$recordingDir}/{$userField}";
                if (file_exists($fullPath)) {
                    return $fullPath;
                }
            }
        }
        
        // 2. Check AccountCode (sometimes used for recording filename)
        if (!empty($accountCode) && str_contains($accountCode, '.wav')) {
            $fullPath = "{$recordingDir}/{$accountCode}";
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }
        
        // 3. Try standard naming conventions based on UniqueID
        if ($uniqueId) {
            $possibleNames = [
                "{$uniqueId}.wav",
                "{$uniqueId}.mp3",
                "{$uniqueId}.gsm",
                // Date-based subdirectory format
                date('Y/m/d') . "/{$uniqueId}.wav",
                date('Y/m/d') . "/{$uniqueId}.mp3",
            ];
            
            foreach ($possibleNames as $name) {
                $fullPath = "{$recordingDir}/{$name}";
                if (file_exists($fullPath)) {
                    return $fullPath;
                }
            }
        }
        
        return null;
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
                        broadcast(new ExtensionStatusChanged($extension, $previousStatus, 'ringing'));
                    }
                } elseif ($subEvent === 'End' || $event['Event'] === 'DialEnd') {
                    $dialStatus = $event['DialStatus'] ?? '';
                    
                    if ($dialStatus === 'ANSWER') {
                        // Call answered - set to on_call
                        $extension->status = 'on_call';
                        $extension->saveQuietly();
                        broadcast(new ExtensionStatusChanged($extension, 'ringing', 'on_call'));
                    } else {
                        // Call not answered - reset to online
                        $extension->status = 'online';
                        $extension->saveQuietly();
                        broadcast(new ExtensionStatusChanged($extension, 'ringing', 'online'));
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

        // Parse the URI to extract IP, port, and transport info
        $registrationInfo = $this->parseContactUri($uri);
        $ipAddress = $registrationInfo['local_ip'];
        $publicIp = $registrationInfo['public_ip'];
        $port = $registrationInfo['port'];
        $transport = $registrationInfo['transport'];
        $isWebRtc = $registrationInfo['is_webrtc'] ?? false;

        // Try to get the real public IP from ps_contacts (essential for WebRTC)
        $viaAddr = $this->getViaAddrFromContacts($extension);
        if ($viaAddr && filter_var($viaAddr, FILTER_VALIDATE_IP)) {
            $publicIp = $viaAddr;
            // For WebRTC, use via_addr as the display IP since local_ip is .invalid
            if ($isWebRtc || !$ipAddress) {
                $ipAddress = $viaAddr;
            }
        }

        // Filter out .invalid hostnames - these are WebRTC instance IDs, not real IPs
        if ($ipAddress && str_contains($ipAddress, '.invalid')) {
            $ipAddress = $publicIp; // Use public IP instead, or null
        }

        $previousStatus = $extensionModel->status;
        $updateData = ['status' => $status];
        
        // Only update registration info if becoming reachable
        if (in_array($contactStatus, ['Reachable', 'Created', 'Updated'])) {
            // Log registration to history
            $this->logRegistration(
                $extensionModel,
                $contactStatus === 'Removed' ? 'unregistered' : 'registered',
                $publicIp,
                $ipAddress,
                $port,
                $transport,
                $userAgent,
                $uri,
                $event
            );
            $updateData['last_registered_at'] = now();
            // Only store valid IPs (not .invalid hostnames)
            if ($ipAddress && filter_var($ipAddress, FILTER_VALIDATE_IP)) {
                $updateData['last_registered_ip'] = $ipAddress;
            } elseif ($publicIp && filter_var($publicIp, FILTER_VALIDATE_IP)) {
                $updateData['last_registered_ip'] = $publicIp;
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

    /**
     * Parse a SIP contact URI to extract IP, port, transport, and public IP info
     * 
     * Format examples:
     * - sip:abc123@192.168.1.100:5060
     * - sip:abc123@10.0.0.5:60066;transport=WS;x-ast-orig-host=randomstring.invalid:0
     * - sip:ext@192.168.1.100:5060;transport=TLS
     */
    private function parseContactUri(?string $uri): array
    {
        $result = [
            'local_ip' => null,
            'public_ip' => null,
            'port' => null,
            'transport' => 'udp', // default
            'is_webrtc' => false,
        ];

        if (!$uri) {
            return $result;
        }

        // Extract transport from URI parameters
        if (preg_match('/transport=(\w+)/i', $uri, $matches)) {
            $result['transport'] = strtolower($matches[1]);
            // Mark as WebRTC if transport is WS or WSS
            $result['is_webrtc'] = in_array(strtolower($matches[1]), ['ws', 'wss']);
        }

        // Extract the host:port part from the URI (sip:user@host:port)
        if (preg_match('/@([^:;]+)(?::(\d+))?/', $uri, $matches)) {
            $host = $matches[1];
            $result['port'] = isset($matches[2]) ? (int) $matches[2] : null;
            
            // Check if host looks like an IP address
            if (filter_var($host, FILTER_VALIDATE_IP)) {
                $result['local_ip'] = $host;
                
                // If it's a public IP (not private range), use it as public_ip too
                if ($this->isPublicIp($host)) {
                    $result['public_ip'] = $host;
                }
            } elseif (str_contains($host, '.invalid')) {
                // WebRTC instance ID - don't store as IP, mark as WebRTC
                $result['is_webrtc'] = true;
                // Don't store the .invalid hostname as local_ip
            } else {
                // Regular hostname
                $result['local_ip'] = $host;
            }
        }

        return $result;
    }

    /**
     * Check if an IP address is a public (non-private) IP
     */
    private function isPublicIp(string $ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false; // For now, only check IPv4
        }

        // Private ranges: 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16, 127.0.0.0/8
        $privateRanges = [
            ['10.0.0.0', '10.255.255.255'],
            ['172.16.0.0', '172.31.255.255'],
            ['192.168.0.0', '192.168.255.255'],
            ['127.0.0.0', '127.255.255.255'],
        ];

        $ipLong = ip2long($ip);

        foreach ($privateRanges as [$start, $end]) {
            if ($ipLong >= ip2long($start) && $ipLong <= ip2long($end)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Log extension registration to history table
     */
    private function logRegistration(
        Extension $extension,
        string $eventType,
        ?string $publicIp,
        ?string $localIp,
        ?int $port,
        ?string $transport,
        ?string $userAgent,
        ?string $contactUri,
        array $rawEvent = []
    ): void {
        try {
            // Try to get via_addr from ps_contacts for public IP (more reliable for WebRTC)
            $viaAddr = $this->getViaAddrFromContacts($extension->extension);
            if ($viaAddr && filter_var($viaAddr, FILTER_VALIDATE_IP)) {
                $publicIp = $viaAddr;
            }

            // Clean up: don't store .invalid hostnames as IPs
            if ($publicIp && str_contains($publicIp, '.invalid')) {
                $publicIp = null;
            }
            if ($localIp && str_contains($localIp, '.invalid')) {
                $localIp = null;
            }

            // Don't log too frequently - dedupe within 1 minute
            $recentReg = ExtensionRegistration::where('extension_id', $extension->id)
                ->where('event_type', $eventType)
                ->where('registered_at', '>=', now()->subMinute())
                ->first();

            if ($recentReg) {
                // Update existing registration record with new data (if better)
                $updateData = [];
                if ($publicIp && !$recentReg->public_ip) {
                    $updateData['public_ip'] = $publicIp;
                }
                if ($localIp && !$recentReg->local_ip) {
                    $updateData['local_ip'] = $localIp;
                }
                if ($port) {
                    $updateData['port'] = $port;
                }
                if ($transport) {
                    $updateData['transport'] = $transport;
                }
                if ($userAgent && $userAgent !== 'Unknown') {
                    $updateData['user_agent'] = $userAgent;
                }
                
                if (!empty($updateData)) {
                    $recentReg->update($updateData);
                }
                return;
            }

            ExtensionRegistration::create([
                'extension_id' => $extension->id,
                'public_ip' => $publicIp,
                'local_ip' => $localIp,
                'port' => $port,
                'transport' => $transport,
                'user_agent' => $userAgent,
                'contact_uri' => $contactUri,
                'event_type' => $eventType,
                'expiry' => $rawEvent['Expiry'] ?? null,
                'metadata' => [
                    'endpoint' => $rawEvent['Endpoint'] ?? null,
                    'aor' => $rawEvent['AOR'] ?? null,
                    'contact_status' => $rawEvent['ContactStatus'] ?? null,
                ],
                'registered_at' => now(),
            ]);

            // Update extension's public_ip field (only if we have a valid IP)
            if ($publicIp && $eventType === 'registered' && filter_var($publicIp, FILTER_VALIDATE_IP)) {
                $extension->update(['public_ip' => $publicIp]);
            }

            if ($this->option('debug')) {
                $this->info("Registration logged: {$extension->extension} ({$eventType}) from " . ($publicIp ?? 'WebRTC'));
            }
        } catch (\Exception $e) {
            Log::error('Failed to log registration', [
                'extension' => $extension->extension,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the via_addr (public IP) from ps_contacts table
     * This is the source IP that Asterisk sees for incoming SIP packets
     * 
     * For WebRTC connections, this is the only reliable way to get the client's public IP
     * because the Contact URI contains a random .invalid hostname
     */
    private function getViaAddrFromContacts(string $extension, int $retries = 3): ?string
    {
        // Try a few times with small delays in case Asterisk hasn't written the data yet
        for ($i = 0; $i < $retries; $i++) {
            try {
                // Query by endpoint or by id containing the extension
                $contact = \DB::table('ps_contacts')
                    ->where(function ($q) use ($extension) {
                        $q->where('endpoint', $extension)
                          ->orWhere('id', 'like', $extension . ';%')
                          ->orWhere('id', 'like', $extension . '@%');
                    })
                    ->orderBy('expiration_time', 'desc')
                    ->first();

                if ($contact) {
                    // via_addr contains the real source IP for WebRTC connections
                    if (!empty($contact->via_addr)) {
                        return $contact->via_addr;
                    }
                    
                    // Fallback: try to extract IP from reg_server or other fields
                    if (!empty($contact->reg_server) && filter_var($contact->reg_server, FILTER_VALIDATE_IP)) {
                        return $contact->reg_server;
                    }
                }
                
                // Wait a bit before retrying
                if ($i < $retries - 1) {
                    usleep(100000); // 100ms
                }
            } catch (\Exception $e) {
                // Table might not exist or be accessible
                if ($this->option('debug')) {
                    $this->warn("Error getting via_addr: " . $e->getMessage());
                }
            }
        }

        return null;
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
