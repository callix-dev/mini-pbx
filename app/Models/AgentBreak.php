<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'break_code_id',
        'started_at',
        'ended_at',
        'duration',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function breakCode(): BelongsTo
    {
        return $this->belongsTo(BreakCode::class);
    }

    public function end(): void
    {
        $this->update([
            'ended_at' => now(),
            'duration' => now()->diffInSeconds($this->started_at),
        ]);
    }

    public function isActive(): bool
    {
        return is_null($this->ended_at);
    }

    public function getFormattedDurationAttribute(): string
    {
        $duration = $this->duration ?? now()->diffInSeconds($this->started_at);
        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $seconds = $duration % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('ended_at');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('started_at', today());
    }
}


