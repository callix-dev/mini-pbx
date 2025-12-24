<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Extension extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'extension',
        'name',
        'password',
        'context',
        'user_id',
        'is_active',
        'status',
        'voicemail_enabled',
        'voicemail_password',
        'voicemail_email',
        'caller_id_name',
        'caller_id_number',
        'settings',
        'last_registered_at',
        'last_registered_ip',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'voicemail_enabled' => 'boolean',
        'settings' => 'array',
        'last_registered_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
        'voicemail_password',
    ];

    /**
     * Get the user assigned to this extension.
     * User has extension_id, so this is a HasOne relationship.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'extension_id');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(ExtensionGroup::class)
            ->withPivot('priority')
            ->withTimestamps();
    }

    public function queueMemberships(): HasMany
    {
        return $this->hasMany(QueueMember::class);
    }

    public function queues(): BelongsToMany
    {
        return $this->belongsToMany(Queue::class, 'queue_members')
            ->withPivot(['penalty', 'paused', 'pause_reason', 'is_logged_in', 'auto_login'])
            ->withTimestamps();
    }

    public function voicemails(): HasMany
    {
        return $this->hasMany(Voicemail::class);
    }

    public function voicemailGreetings(): HasMany
    {
        return $this->hasMany(VoicemailGreeting::class);
    }

    public function callLogs(): HasMany
    {
        return $this->hasMany(CallLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOnline($query)
    {
        return $query->whereIn('status', ['online', 'ringing', 'on_call']);
    }

    public function isOnline(): bool
    {
        return in_array($this->status, ['online', 'ringing', 'on_call']);
    }
}

