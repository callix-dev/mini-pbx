<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoundboardClip extends Model
{
    use HasFactory;

    protected $fillable = [
        'soundboard_id',
        'name',
        'original_filename',
        'file_path',
        'converted_path',
        'duration',
        'shortcut_key',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function soundboard(): BelongsTo
    {
        return $this->belongsTo(Soundboard::class);
    }

    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration) {
            return '0:00';
        }
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }
}


