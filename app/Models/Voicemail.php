<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voicemail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'extension_id',
        'caller_id',
        'caller_name',
        'file_path',
        'duration',
        'is_read',
        'read_at',
        'transcription',
        'is_forwarded',
        'forwarded_from_id',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_forwarded' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function extension(): BelongsTo
    {
        return $this->belongsTo(Extension::class);
    }

    public function forwardedFrom(): BelongsTo
    {
        return $this->belongsTo(Voicemail::class, 'forwarded_from_id');
    }

    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    public function markAsUnread(): void
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function forward(Extension $toExtension): Voicemail
    {
        return self::create([
            'extension_id' => $toExtension->id,
            'caller_id' => $this->caller_id,
            'caller_name' => $this->caller_name,
            'file_path' => $this->file_path,
            'duration' => $this->duration,
            'is_read' => false,
            'is_forwarded' => true,
            'forwarded_from_id' => $this->id,
        ]);
    }

    public function getFormattedDurationAttribute(): string
    {
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}





