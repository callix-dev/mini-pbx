<?php

namespace App\Events;

use App\Models\Queue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $queueData;

    public function __construct(array $queueData)
    {
        $this->queueData = $queueData;
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('dashboard'),
            new Channel('queues'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'queue.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'queue_id' => $this->queueData['queue_id'] ?? null,
            'queue_name' => $this->queueData['queue_name'] ?? null,
            'waiting' => $this->queueData['waiting'] ?? 0,
            'agents_available' => $this->queueData['agents_available'] ?? 0,
            'agents_busy' => $this->queueData['agents_busy'] ?? 0,
            'avg_wait_time' => $this->queueData['avg_wait_time'] ?? 0,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

