<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExtensionGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'group_number',
        'pickup_group',
        'description',
        'ring_strategy',
        'ring_time',
        'music_on_hold',
        'announce_holdtime',
        'announce_position',
        'timeout_destination_type',
        'timeout_destination_id',
        'failover_destination_type',
        'failover_destination_id',
        'record_calls',
        'is_active',
        'settings',
        'total_calls',
        'answered_calls',
        'missed_calls',
        'total_talk_time',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'announce_holdtime' => 'boolean',
        'announce_position' => 'boolean',
        'record_calls' => 'boolean',
        'settings' => 'array',
        'total_calls' => 'integer',
        'answered_calls' => 'integer',
        'missed_calls' => 'integer',
        'total_talk_time' => 'integer',
    ];

    public const RING_STRATEGIES = [
        'ringall' => 'Ring All',
        'hunt' => 'Hunt (Linear)',
        'memoryhunt' => 'Memory Hunt',
        'leastrecent' => 'Least Recent',
        'fewestcalls' => 'Fewest Calls',
        'random' => 'Random',
        'rrmemory' => 'Round Robin (Memory)',
    ];

    public const DESTINATION_TYPES = [
        'extension' => 'Extension',
        'voicemail' => 'Voicemail',
        'queue' => 'Queue',
        'ivr' => 'IVR',
        'hangup' => 'Hangup',
        'external' => 'External Number',
    ];

    public function extensions(): BelongsToMany
    {
        return $this->belongsToMany(Extension::class)
            ->withPivot('priority')
            ->withTimestamps()
            ->orderByPivot('priority');
    }

    /**
     * Get active (online) extensions in this group
     */
    public function activeExtensions(): BelongsToMany
    {
        return $this->extensions()->where('status', 'online');
    }

    /**
     * Get the timeout destination model
     */
    public function timeoutDestination(): MorphTo
    {
        return $this->morphTo('timeout_destination');
    }

    /**
     * Get the failover destination model
     */
    public function failoverDestination(): MorphTo
    {
        return $this->morphTo('failover_destination');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getRingStrategyLabelAttribute(): string
    {
        return self::RING_STRATEGIES[$this->ring_strategy] ?? $this->ring_strategy;
    }

    /**
     * Get the feature code to dial this group (*6XX format)
     */
    public function getDialCodeAttribute(): string
    {
        return $this->group_number ? '*6' . $this->group_number : '';
    }

    /**
     * Get online/offline/on_call counts for the group
     */
    public function getMemberStatusCountsAttribute(): array
    {
        $extensions = $this->extensions;
        
        return [
            'total' => $extensions->count(),
            'online' => $extensions->where('status', 'online')->count(),
            'on_call' => $extensions->where('status', 'on_call')->count(),
            'ringing' => $extensions->where('status', 'ringing')->count(),
            'offline' => $extensions->where('status', 'offline')->count(),
        ];
    }

    /**
     * Get the answer rate percentage
     */
    public function getAnswerRateAttribute(): float
    {
        if ($this->total_calls === 0) {
            return 0;
        }
        return round(($this->answered_calls / $this->total_calls) * 100, 1);
    }

    /**
     * Get average talk time in seconds
     */
    public function getAverageTalkTimeAttribute(): int
    {
        if ($this->answered_calls === 0) {
            return 0;
        }
        return (int) round($this->total_talk_time / $this->answered_calls);
    }

    /**
     * Get formatted average talk time
     */
    public function getFormattedAvgTalkTimeAttribute(): string
    {
        $seconds = $this->average_talk_time;
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return sprintf('%02d:%02d', $minutes, $secs);
    }

    /**
     * Generate Asterisk Dial string for this group
     */
    public function getDialStringAttribute(): string
    {
        $extensions = $this->extensions()->where('is_active', true)->get();
        
        if ($extensions->isEmpty()) {
            return '';
        }

        // Build dial string based on ring strategy
        $endpoints = $extensions->map(function ($ext) {
            return 'PJSIP/' . $ext->extension;
        });

        return $endpoints->implode('&');
    }

    /**
     * Generate dial string with prioritized order (for hunt strategies)
     */
    public function getPrioritizedDialListAttribute(): array
    {
        return $this->extensions()
            ->where('is_active', true)
            ->orderByPivot('priority')
            ->get()
            ->map(fn($ext) => 'PJSIP/' . $ext->extension)
            ->toArray();
    }

    /**
     * Increment call statistics
     */
    public function recordCall(bool $answered, int $talkTime = 0): void
    {
        $this->increment('total_calls');
        
        if ($answered) {
            $this->increment('answered_calls');
            if ($talkTime > 0) {
                $this->increment('total_talk_time', $talkTime);
            }
        } else {
            $this->increment('missed_calls');
        }
    }

    /**
     * Reset statistics
     */
    public function resetStatistics(): void
    {
        $this->update([
            'total_calls' => 0,
            'answered_calls' => 0,
            'missed_calls' => 0,
            'total_talk_time' => 0,
        ]);
    }

    /**
     * Sync pickup_group to all member extensions
     */
    public function syncPickupGroupToMembers(): void
    {
        if ($this->pickup_group) {
            $this->extensions()->update(['pickup_group' => $this->pickup_group]);
        }
    }
}
