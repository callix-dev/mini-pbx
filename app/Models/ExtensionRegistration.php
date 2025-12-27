<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtensionRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'extension_id',
        'public_ip',
        'local_ip',
        'port',
        'transport',
        'user_agent',
        'contact_uri',
        'event_type',
        'expiry',
        'metadata',
        'registered_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'registered_at' => 'datetime',
        'port' => 'integer',
        'expiry' => 'integer',
    ];

    public function extension(): BelongsTo
    {
        return $this->belongsTo(Extension::class);
    }

    /**
     * Get formatted transport type
     */
    public function getTransportLabelAttribute(): string
    {
        return match (strtolower($this->transport ?? '')) {
            'ws', 'wss' => 'WebRTC',
            'udp' => 'UDP',
            'tcp' => 'TCP',
            'tls' => 'TLS',
            default => $this->transport ?? 'Unknown',
        };
    }

    /**
     * Check if this was a WebRTC registration
     */
    public function isWebRtc(): bool
    {
        return in_array(strtolower($this->transport ?? ''), ['ws', 'wss']);
    }

    /**
     * Scope to get registrations from last X days
     */
    public function scopeLastDays($query, int $days = 30)
    {
        return $query->where('registered_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for only registration events (not unregistered)
     */
    public function scopeRegistered($query)
    {
        return $query->where('event_type', 'registered');
    }
}



