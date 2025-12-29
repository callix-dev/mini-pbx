<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Carrier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'provider_slug',
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
        'backup_carrier_id',
        'settings',
        'provider_config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'codecs' => 'array',
        'settings' => 'array',
        'provider_config' => 'array',
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

    /**
     * Get the DIDs associated with this carrier
     */
    public function dids(): HasMany
    {
        return $this->hasMany(Did::class);
    }

    /**
     * Get the call logs for this carrier
     */
    public function callLogs(): HasMany
    {
        return $this->hasMany(CallLog::class);
    }

    /**
     * Get the backup/failover carrier
     */
    public function backupCarrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'backup_carrier_id');
    }

    /**
     * Get carriers that use this carrier as backup
     */
    public function primaryCarriers(): HasMany
    {
        return $this->hasMany(Carrier::class, 'backup_carrier_id');
    }

    /**
     * Scope: Active carriers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Inbound carriers
     */
    public function scopeInbound($query)
    {
        return $query->where('type', 'inbound');
    }

    /**
     * Scope: Outbound carriers
     */
    public function scopeOutbound($query)
    {
        return $query->where('type', 'outbound');
    }

    /**
     * Scope: By provider
     */
    public function scopeProvider($query, string $providerSlug)
    {
        return $query->where('provider_slug', $providerSlug);
    }

    /**
     * Get provider display name
     */
    public function getProviderNameAttribute(): ?string
    {
        if (!$this->provider_slug) {
            return null;
        }
        $providers = CarrierTemplate::getProviders();
        return $providers[$this->provider_slug] ?? ucfirst($this->provider_slug);
    }

    /**
     * Get the PJSIP endpoint name for this carrier
     */
    public function getPjsipEndpointName(): string
    {
        // Use name sanitized for PJSIP (lowercase, underscores)
        return strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $this->name));
    }

    /**
     * Check if this carrier uses registration-based auth
     */
    public function usesRegistration(): bool
    {
        return $this->auth_type === 'registration';
    }

    /**
     * Check if this carrier uses IP-based auth
     */
    public function usesIpAuth(): bool
    {
        return $this->auth_type === 'ip';
    }

    /**
     * Get a provider config value
     */
    public function getProviderConfigValue(string $key, $default = null)
    {
        return $this->provider_config[$key] ?? $default;
    }

    /**
     * Get the outbound proxy (for providers like RingCentral)
     */
    public function getOutboundProxy(): ?string
    {
        return $this->getProviderConfigValue('outbound_proxy');
    }
}







