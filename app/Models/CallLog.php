<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CallLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uniqueid',
        'linkedid',
        'type',
        'direction',
        'caller_id',
        'caller_name',
        'callee_id',
        'callee_name',
        'did',
        'extension_id',
        'queue_id',
        'carrier_id',
        'status',
        'start_time',
        'answer_time',
        'end_time',
        'duration',
        'billable_duration',
        'wait_time',
        'hangup_cause',
        'hangup_by',
        'recording_path',
        'disposition_id',
        'notes',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'answer_time' => 'datetime',
        'end_time' => 'datetime',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    public const TYPES = [
        'inbound' => 'Inbound',
        'outbound' => 'Outbound',
        'internal' => 'Internal',
    ];

    public const STATUSES = [
        'answered' => 'Answered',
        'missed' => 'Missed',
        'busy' => 'Busy',
        'failed' => 'Failed',
        'voicemail' => 'Voicemail',
    ];

    public function extension(): BelongsTo
    {
        return $this->belongsTo(Extension::class);
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function disposition(): BelongsTo
    {
        return $this->belongsTo(Disposition::class);
    }

    public function callNotes(): HasMany
    {
        return $this->hasMany(CallNote::class)->orderBy('created_at', 'desc');
    }

    public function callbacks(): HasMany
    {
        return $this->hasMany(Callback::class);
    }

    public function scopeInbound($query)
    {
        return $query->where('type', 'inbound');
    }

    public function scopeOutbound($query)
    {
        return $query->where('type', 'outbound');
    }

    public function scopeMissed($query)
    {
        return $query->where('status', 'missed');
    }

    public function scopeAnswered($query)
    {
        return $query->where('status', 'answered');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('start_time', today());
    }

    public function getFormattedDurationAttribute(): string
    {
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function hasRecording(): bool
    {
        return !empty($this->recording_path);
    }
}


