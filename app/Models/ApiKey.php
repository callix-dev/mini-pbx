<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'key',
        'secret_hash',
        'permissions',
        'ip_whitelist',
        'rate_limit',
        'last_used_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'permissions' => 'array',
        'ip_whitelist' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'secret_hash',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ApiLog::class);
    }

    public static function generate(User $user, string $name, array $permissions = [], ?array $ipWhitelist = null): array
    {
        $key = 'pbx_' . Str::random(32);
        $secret = Str::random(64);

        $apiKey = self::create([
            'user_id' => $user->id,
            'name' => $name,
            'key' => $key,
            'secret_hash' => bcrypt($secret),
            'permissions' => $permissions,
            'ip_whitelist' => $ipWhitelist,
        ]);

        return [
            'api_key' => $apiKey,
            'key' => $key,
            'secret' => $secret, // Only returned once at creation
        ];
    }

    public function validateSecret(string $secret): bool
    {
        return password_verify($secret, $this->secret_hash);
    }

    public function hasPermission(string $permission): bool
    {
        if (empty($this->permissions)) {
            return true; // Full access if no specific permissions set
        }

        return in_array($permission, $this->permissions) || in_array('*', $this->permissions);
    }

    public function isIpAllowed(string $ip): bool
    {
        if (empty($this->ip_whitelist)) {
            return true;
        }

        return in_array($ip, $this->ip_whitelist);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    public function recordUsage(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}


