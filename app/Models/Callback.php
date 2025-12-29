<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Callback extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'call_log_id',
        'phone_number',
        'contact_name',
        'notes',
        'scheduled_at',
        'reminded_at',
        'completed_at',
        'status',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'reminded_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'reminded' => 'Reminded',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function callLog(): BelongsTo
    {
        return $this->belongsTo(CallLog::class);
    }

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function markReminded(): void
    {
        $this->update([
            'status' => 'reminded',
            'reminded_at' => now(),
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDue($query)
    {
        return $query->where('scheduled_at', '<=', now())
            ->whereIn('status', ['pending', 'reminded']);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now())
            ->where('status', 'pending');
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->scheduled_at->isPast();
    }
}







