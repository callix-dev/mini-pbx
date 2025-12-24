<?php

namespace App\Services\Asterisk;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

class AmiService
{
    protected $socket;
    protected string $host;
    protected int $port;
    protected string $username;
    protected string $password;
    protected bool $connected = false;

    public function __construct()
    {
        $this->host = SystemSetting::get('host', '127.0.0.1', 'ami');
        $this->port = (int) SystemSetting::get('port', 5038, 'ami');
        $this->username = SystemSetting::get('username', 'admin', 'ami');
        $this->password = SystemSetting::get('password', '', 'ami');
    }

    public function connect(): bool
    {
        if ($this->connected) {
            return true;
        }

        try {
            $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, 5);
            
            if (!$this->socket) {
                Log::error("AMI connection failed: {$errstr} ({$errno})");
                return false;
            }

            stream_set_timeout($this->socket, 5);

            // Read banner
            $this->readResponse();

            // Login
            $response = $this->sendAction([
                'Action' => 'Login',
                'Username' => $this->username,
                'Secret' => $this->password,
            ]);

            if (strpos($response, 'Success') !== false) {
                $this->connected = true;
                return true;
            }

            Log::error('AMI login failed');
            $this->disconnect();
            return false;
        } catch (\Exception $e) {
            Log::error('AMI connection error: ' . $e->getMessage());
            return false;
        }
    }

    public function disconnect(): void
    {
        if ($this->socket) {
            $this->sendAction(['Action' => 'Logoff']);
            fclose($this->socket);
            $this->socket = null;
            $this->connected = false;
        }
    }

    public function sendAction(array $action): string
    {
        if (!$this->connected && $action['Action'] !== 'Login') {
            if (!$this->connect()) {
                return '';
            }
        }

        $message = '';
        foreach ($action as $key => $value) {
            $message .= "{$key}: {$value}\r\n";
        }
        $message .= "\r\n";

        fwrite($this->socket, $message);

        return $this->readResponse();
    }

    protected function readResponse(): string
    {
        $response = '';
        while (($line = fgets($this->socket)) !== false) {
            $response .= $line;
            if (trim($line) === '') {
                break;
            }
        }
        return $response;
    }

    public function testConnection(): bool
    {
        $connected = $this->connect();
        if ($connected) {
            $this->disconnect();
        }
        return $connected;
    }

    // Call origination
    public function originate(string $channel, string $extension, string $context = 'from-internal', string $callerId = '', int $timeout = 30000): array
    {
        $response = $this->sendAction([
            'Action' => 'Originate',
            'Channel' => $channel,
            'Exten' => $extension,
            'Context' => $context,
            'Priority' => 1,
            'CallerID' => $callerId,
            'Timeout' => $timeout,
            'Async' => 'true',
        ]);

        return $this->parseResponse($response);
    }

    // Hangup a channel
    public function hangup(string $channel): array
    {
        $response = $this->sendAction([
            'Action' => 'Hangup',
            'Channel' => $channel,
        ]);

        return $this->parseResponse($response);
    }

    // Spy on a call (ChanSpy)
    public function spy(string $spyChannel, string $targetChannel, string $options = 'qw'): array
    {
        $response = $this->sendAction([
            'Action' => 'Originate',
            'Channel' => $spyChannel,
            'Application' => 'ChanSpy',
            'Data' => "{$targetChannel},{$options}",
            'Async' => 'true',
        ]);

        return $this->parseResponse($response);
    }

    // Redirect (blind transfer)
    public function redirect(string $channel, string $extension, string $context = 'from-internal'): array
    {
        $response = $this->sendAction([
            'Action' => 'Redirect',
            'Channel' => $channel,
            'Exten' => $extension,
            'Context' => $context,
            'Priority' => 1,
        ]);

        return $this->parseResponse($response);
    }

    // Park a call
    public function park(string $channel, string $channel2, int $timeout = 45, string $parkingLot = 'default'): array
    {
        $response = $this->sendAction([
            'Action' => 'Park',
            'Channel' => $channel,
            'Channel2' => $channel2,
            'Timeout' => $timeout * 1000,
            'ParkingLot' => $parkingLot,
        ]);

        return $this->parseResponse($response);
    }

    // Get active channels
    public function getChannels(): array
    {
        $response = $this->sendAction([
            'Action' => 'CoreShowChannels',
        ]);

        // Parse channel list from response
        return $this->parseChannelList($response);
    }

    // Queue actions
    public function queueAdd(string $queue, string $interface, int $penalty = 0): array
    {
        $response = $this->sendAction([
            'Action' => 'QueueAdd',
            'Queue' => $queue,
            'Interface' => $interface,
            'Penalty' => $penalty,
        ]);

        return $this->parseResponse($response);
    }

    public function queueRemove(string $queue, string $interface): array
    {
        $response = $this->sendAction([
            'Action' => 'QueueRemove',
            'Queue' => $queue,
            'Interface' => $interface,
        ]);

        return $this->parseResponse($response);
    }

    public function queuePause(string $queue, string $interface, bool $paused, string $reason = ''): array
    {
        $response = $this->sendAction([
            'Action' => 'QueuePause',
            'Queue' => $queue,
            'Interface' => $interface,
            'Paused' => $paused ? 'true' : 'false',
            'Reason' => $reason,
        ]);

        return $this->parseResponse($response);
    }

    // Reload Asterisk module
    public function reload(string $module = ''): array
    {
        $action = ['Action' => 'Reload'];
        if ($module) {
            $action['Module'] = $module;
        }

        $response = $this->sendAction($action);
        return $this->parseResponse($response);
    }

    protected function parseResponse(string $response): array
    {
        $lines = explode("\r\n", $response);
        $result = ['success' => false, 'message' => '', 'data' => []];

        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $key = trim($key);
                $value = trim($value);

                if ($key === 'Response') {
                    $result['success'] = $value === 'Success';
                } elseif ($key === 'Message') {
                    $result['message'] = $value;
                } else {
                    $result['data'][$key] = $value;
                }
            }
        }

        return $result;
    }

    protected function parseChannelList(string $response): array
    {
        // This is a simplified parser - real implementation would need more parsing
        $channels = [];
        $lines = explode("\r\n", $response);
        
        // Parse channel entries
        foreach ($lines as $line) {
            if (preg_match('/^Channel:\s*(.+)/', $line, $matches)) {
                $channels[] = trim($matches[1]);
            }
        }

        return $channels;
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}

