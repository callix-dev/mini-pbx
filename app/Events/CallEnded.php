<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallEnded implements ShouldBroadcast
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
        return 'call.ended';
    }

    public function broadcastWith(): array
    {
        return [
            'unique_id' => $this->callData['unique_id'] ?? null,
            'channel' => $this->callData['channel'] ?? null,
            'duration' => $this->callData['duration'] ?? 0,
            'status' => $this->callData['status'] ?? 'completed',
            'ended_at' => now()->toIso8601String(),
        ];
    }
}



