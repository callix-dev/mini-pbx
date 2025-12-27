<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Queue extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'strategy',
        'timeout',
        'retry',
        'wrapuptime',
        'maxlen',
        'weight',
        'joinempty',
        'leavewhenempty',
        'hold_music_id',
        'soundboard_id',
        'block_filter_group_id',
        'announce_frequency',
        'announce_holdtime',
        'announce_position',
        'failover_destination_type',
        'failover_destination_id',
        'is_active',
        'record_calls',
        'priority_queue',
        'business_hours',
        'out_of_hours_destination_type',
        'out_of_hours_destination_id',
        'settings',
    ];

    protected $casts = [
        'joinempty' => 'boolean',
        'leavewhenempty' => 'boolean',
        'is_active' => 'boolean',
        'record_calls' => 'boolean',
        'priority_queue' => 'boolean',
        'business_hours' => 'array',
        'settings' => 'array',
    ];

    public const STRATEGIES = [
        'ringall' => 'Ring All',
        'leastrecent' => 'Least Recent',
        'fewestcalls' => 'Fewest Calls',
        'random' => 'Random',
        'rrmemory' => 'Round Robin Memory',
        'linear' => 'Linear',
        'wrandom' => 'Weighted Random',
    ];

    public function holdMusic(): BelongsTo
    {
        return $this->belongsTo(HoldMusic::class);
    }

    public function soundboard(): BelongsTo
    {
        return $this->belongsTo(Soundboard::class);
    }

    public function blockFilterGroup(): BelongsTo
    {
        return $this->belongsTo(BlockFilterGroup::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(QueueMember::class);
    }

    public function extensions(): BelongsToMany
    {
        return $this->belongsToMany(Extension::class, 'queue_members')
            ->withPivot(['penalty', 'paused', 'pause_reason', 'is_logged_in', 'auto_login'])
            ->withTimestamps();
    }

    public function vipCallers(): HasMany
    {
        return $this->hasMany(VipCaller::class);
    }

    public function callLogs(): HasMany
    {
        return $this->hasMany(CallLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getLoggedInAgentsCountAttribute(): int
    {
        return $this->members()->where('is_logged_in', true)->count();
    }

    public function getAvailableAgentsCountAttribute(): int
    {
        return $this->members()
            ->where('is_logged_in', true)
            ->where('paused', false)
            ->whereHas('extension', fn($q) => $q->where('status', 'online'))
            ->count();
    }
}





