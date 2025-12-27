<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Carrier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'technology',
        'host',
        'port',
        'transport',
        'auth_type',
        'username',
        'password',
        'from_domain',
        'from_user',
        'codecs',
        'max_channels',
        'context',
        'is_active',
        'priority',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'codecs' => 'array',
        'settings' => 'array',
    ];

    protected $hidden = [
        'password',
    ];

    public const TYPES = [
        'inbound' => 'Inbound',
        'outbound' => 'Outbound',
    ];

    public const AUTH_TYPES = [
        'ip' => 'IP Authentication',
        'registration' => 'Registration',
    ];

    public const TRANSPORTS = [
        'udp' => 'UDP',
        'tcp' => 'TCP',
        'tls' => 'TLS',
    ];

    public const DEFAULT_CODECS = ['ulaw', 'alaw', 'g722', 'opus'];

    public function dids(): HasMany
    {
        return $this->hasMany(Did::class);
    }

    public function callLogs(): HasMany
    {
        return $this->hasMany(CallLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInbound($query)
    {
        return $query->where('type', 'inbound');
    }

    public function scopeOutbound($query)
    {
        return $query->where('type', 'outbound');
    }
}





