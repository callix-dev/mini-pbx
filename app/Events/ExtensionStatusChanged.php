<?php

namespace App\Events;

use App\Models\Extension;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExtensionStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Extension $extension;
    public string $previousStatus;
    public string $newStatus;

    public function __construct(Extension $extension, string $previousStatus, string $newStatus)
    {
        $this->extension = $extension;
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('extensions'),
            new Channel('extension.' . $this->extension->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'extension.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'extension_id' => $this->extension->id,
            'extension' => $this->extension->extension,
            'name' => $this->extension->name,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
            'user_id' => $this->extension->user?->id,
            'user_name' => $this->extension->user?->name,
            'last_registered_at' => $this->extension->last_registered_at?->toIso8601String(),
            'last_registered_ip' => $this->extension->last_registered_ip,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}



