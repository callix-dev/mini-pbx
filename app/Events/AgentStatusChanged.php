<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $agent;
    public string $previousStatus;
    public string $newStatus;

    public function __construct(User $agent, string $previousStatus, string $newStatus)
    {
        $this->agent = $agent;
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('dashboard'),
            new PresenceChannel('agents'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'agent.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'agent_id' => $this->agent->id,
            'name' => $this->agent->name,
            'extension' => $this->agent->extension?->extension,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}







