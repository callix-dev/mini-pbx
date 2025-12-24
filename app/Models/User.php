<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'agent_status',
        'extension_id',
        'phone',
        'is_active',
        'last_login_at',
        'last_login_ip',
        'session_id',
        'notification_preferences',
        'timezone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'session_id',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'notification_preferences' => 'array',
        ];
    }

    public const AGENT_STATUSES = [
        'offline' => 'Offline',
        'available' => 'Available',
        'on_call' => 'On Call',
        'ringing' => 'Ringing',
        'on_break' => 'On Break',
        'not_ready' => 'Not Ready',
        'wrap_up' => 'Wrap-up',
    ];

    public function extension(): BelongsTo
    {
        return $this->belongsTo(Extension::class);
    }

    public function callbacks(): HasMany
    {
        return $this->hasMany(Callback::class);
    }

    public function agentBreaks(): HasMany
    {
        return $this->hasMany(AgentBreak::class);
    }

    public function currentBreak(): HasOne
    {
        return $this->hasOne(AgentBreak::class)->whereNull('ended_at')->latest();
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function callNotes(): HasMany
    {
        return $this->hasMany(CallNote::class);
    }

    public function createdBackups(): HasMany
    {
        return $this->hasMany(Backup::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAgents($query)
    {
        return $query->role('Agent');
    }

    public function isOnline(): bool
    {
        return in_array($this->agent_status, ['available', 'on_call', 'ringing', 'wrap_up']);
    }

    public function isAvailable(): bool
    {
        return $this->agent_status === 'available';
    }

    public function setStatus(string $status): void
    {
        $this->update(['agent_status' => $status]);
    }

    public function startBreak(BreakCode $breakCode): AgentBreak
    {
        // End any existing break
        $this->currentBreak?->end();

        $this->setStatus('on_break');

        return $this->agentBreaks()->create([
            'break_code_id' => $breakCode->id,
            'started_at' => now(),
        ]);
    }

    public function endBreak(): void
    {
        $this->currentBreak?->end();
        $this->setStatus('available');
    }

    public function recordLogin(): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
            'session_id' => session()->getId(),
        ]);
    }

    public function invalidateOtherSessions(): void
    {
        // This will be called when single session is enforced
        if ($this->session_id && $this->session_id !== session()->getId()) {
            // The old session will be invalidated
        }
    }

    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
        return $initials;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->agent_status) {
            'available' => 'green',
            'on_call', 'ringing' => 'red',
            'on_break' => 'yellow',
            'wrap_up' => 'orange',
            'not_ready' => 'gray',
            default => 'gray',
        };
    }

    public function getIsOnlineAttribute(): bool
    {
        return $this->isOnline();
    }

    public function getIsOnCallAttribute(): bool
    {
        return $this->agent_status === 'on_call';
    }

    public function getIsPausedAttribute(): bool
    {
        return $this->agent_status === 'on_break';
    }

    public function getPauseReasonAttribute(): ?string
    {
        return $this->currentBreak?->breakCode?->name;
    }
}
