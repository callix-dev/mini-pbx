<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HoldMusic extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'hold_music';

    protected $fillable = [
        'name',
        'description',
        'directory_name',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function files(): HasMany
    {
        return $this->hasMany(HoldMusicFile::class)->orderBy('sort_order');
    }

    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function setAsDefault(): void
    {
        // Remove default from all others
        self::where('is_default', true)->update(['is_default' => false]);
        
        $this->update(['is_default' => true]);
    }
}



