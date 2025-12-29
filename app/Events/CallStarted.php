<?php

namespace App\Events;

use App\Models\CallLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $callData;

    public function __construct(array $callData)
    {
        $this->callData = $callData;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('calls'),
            new PresenceChannel('dashboard'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'call.started';
    }

    public function broadcastWith(): array
    {
        return [
            'unique_id' => $this->callData['unique_id'] ?? null,
            'channel' => $this->callData['channel'] ?? null,
            'caller_id' => $this->callData['caller_id'] ?? null,
            'destination' => $this->callData['destination'] ?? null,
            'queue' => $this->callData['queue'] ?? null,
            'extension' => $this->callData['extension'] ?? null,
            'type' => $this->callData['type'] ?? 'inbound',
            'started_at' => now()->toIso8601String(),
        ];
    }
}







