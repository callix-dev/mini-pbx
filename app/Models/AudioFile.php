<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AudioFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'original_filename',
        'file_path',
        'converted_path',
        'duration',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const TYPES = [
        'ivr_prompt' => 'IVR Prompt',
        'announcement' => 'Announcement',
        'greeting' => 'Greeting',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
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







