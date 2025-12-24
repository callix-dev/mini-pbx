<?php

namespace App\Console\Commands;

use App\Events\AgentStatusChanged;
use App\Events\CallEnded;
use App\Events\CallStarted;
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

    public function handle(): int
    {
        $this->info('Starting AMI Listener...');

        $host = SystemSetting::getValue('ami_host', '127.0.0.1');
        $port = SystemSetting::getValue('ami_port', 5038);
        $username = SystemSetting::getValue('ami_username', 'admin');
        $secret = SystemSetting::getValue('ami_secret', '');

        try {
            $this->connect($host, $port);
            $this->login($username, $secret);

            $this->info('Connected to AMI. Listening for events...');

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
            return 1;
        }

        return 0;
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
            $this->line("Event: $eventType");
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
                $status = match ($state) {
                    'NOT_INUSE' => 'online',
                    'INUSE', 'BUSY' => 'on_call',
                    'RINGING' => 'ringing',
                    default => 'offline',
                };

                $extensionModel->update(['status' => $status]);
            }
        }
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

