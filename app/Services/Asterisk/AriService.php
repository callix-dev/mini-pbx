<?php

namespace App\Services\Asterisk;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AriService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;

    public function __construct()
    {
        $host = SystemSetting::get('host', '127.0.0.1', 'ari');
        $port = SystemSetting::get('port', 8088, 'ari');
        $this->username = SystemSetting::get('username', 'admin', 'ari');
        $this->password = SystemSetting::get('password', '', 'ari');
        
        $this->baseUrl = "http://{$host}:{$port}/ari";
    }

    protected function request(string $method, string $endpoint, array $data = []): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(10)
                ->{$method}($this->baseUrl . $endpoint, $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => $response->body(),
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('ARI request failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function testConnection(): bool
    {
        $result = $this->request('get', '/asterisk/info');
        return $result['success'] ?? false;
    }

    // Asterisk info
    public function getAsteriskInfo(): array
    {
        return $this->request('get', '/asterisk/info');
    }

    // Channels
    public function getChannels(): array
    {
        return $this->request('get', '/channels');
    }

    public function getChannel(string $channelId): array
    {
        return $this->request('get', "/channels/{$channelId}");
    }

    public function originateChannel(string $endpoint, array $options = []): array
    {
        $data = array_merge([
            'endpoint' => $endpoint,
            'app' => 'mini-pbx',
        ], $options);

        return $this->request('post', '/channels', $data);
    }

    public function hangupChannel(string $channelId, string $reason = 'normal'): array
    {
        return $this->request('delete', "/channels/{$channelId}", [
            'reason_code' => $reason,
        ]);
    }

    public function answerChannel(string $channelId): array
    {
        return $this->request('post', "/channels/{$channelId}/answer");
    }

    public function holdChannel(string $channelId): array
    {
        return $this->request('post', "/channels/{$channelId}/hold");
    }

    public function unholdChannel(string $channelId): array
    {
        return $this->request('delete', "/channels/{$channelId}/hold");
    }

    public function muteChannel(string $channelId, string $direction = 'both'): array
    {
        return $this->request('post', "/channels/{$channelId}/mute", [
            'direction' => $direction,
        ]);
    }

    public function unmuteChannel(string $channelId, string $direction = 'both'): array
    {
        return $this->request('delete', "/channels/{$channelId}/mute", [
            'direction' => $direction,
        ]);
    }

    // Play audio
    public function playMedia(string $channelId, string $media): array
    {
        return $this->request('post', "/channels/{$channelId}/play", [
            'media' => $media,
        ]);
    }

    // DTMF
    public function sendDTMF(string $channelId, string $dtmf): array
    {
        return $this->request('post', "/channels/{$channelId}/dtmf", [
            'dtmf' => $dtmf,
        ]);
    }

    // Bridges
    public function getBridges(): array
    {
        return $this->request('get', '/bridges');
    }

    public function createBridge(string $type = 'mixing', string $name = ''): array
    {
        return $this->request('post', '/bridges', [
            'type' => $type,
            'name' => $name,
        ]);
    }

    public function addChannelToBridge(string $bridgeId, string $channelId): array
    {
        return $this->request('post', "/bridges/{$bridgeId}/addChannel", [
            'channel' => $channelId,
        ]);
    }

    public function removeChannelFromBridge(string $bridgeId, string $channelId): array
    {
        return $this->request('post', "/bridges/{$bridgeId}/removeChannel", [
            'channel' => $channelId,
        ]);
    }

    public function destroyBridge(string $bridgeId): array
    {
        return $this->request('delete', "/bridges/{$bridgeId}");
    }

    // Endpoints
    public function getEndpoints(): array
    {
        return $this->request('get', '/endpoints');
    }

    public function getEndpoint(string $tech, string $resource): array
    {
        return $this->request('get', "/endpoints/{$tech}/{$resource}");
    }

    // Recordings
    public function getRecordings(): array
    {
        return $this->request('get', '/recordings/stored');
    }

    public function getRecording(string $recordingName): array
    {
        return $this->request('get', "/recordings/stored/{$recordingName}");
    }

    public function deleteRecording(string $recordingName): array
    {
        return $this->request('delete', "/recordings/stored/{$recordingName}");
    }

    // Sounds
    public function getSounds(): array
    {
        return $this->request('get', '/sounds');
    }

    // Device states
    public function getDeviceStates(): array
    {
        return $this->request('get', '/deviceStates');
    }

    public function getDeviceState(string $deviceName): array
    {
        return $this->request('get', "/deviceStates/{$deviceName}");
    }
}

