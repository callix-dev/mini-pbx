<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'queue_id',
        'extension_id',
        'penalty',
        'paused',
        'pause_reason',
        'paused_at',
        'is_logged_in',
        'logged_in_at',
        'auto_login',
    ];

    protected $casts = [
        'paused' => 'boolean',
        'is_logged_in' => 'boolean',
        'auto_login' => 'boolean',
        'paused_at' => 'datetime',
        'logged_in_at' => 'datetime',
    ];

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }

    public function extension(): BelongsTo
    {
        return $this->belongsTo(Extension::class);
    }

    public function login(): void
    {
        $this->update([
            'is_logged_in' => true,
            'logged_in_at' => now(),
            'paused' => false,
            'pause_reason' => null,
        ]);
    }

    public function logout(): void
    {
        $this->update([
            'is_logged_in' => false,
            'logged_in_at' => null,
        ]);
    }

    public function pause(?string $reason = null): void
    {
        $this->update([
            'paused' => true,
            'pause_reason' => $reason,
            'paused_at' => now(),
        ]);
    }

    public function unpause(): void
    {
        $this->update([
            'paused' => false,
            'pause_reason' => null,
            'paused_at' => null,
        ]);
    }
}


