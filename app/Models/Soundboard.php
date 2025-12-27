<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Soundboard extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function clips(): HasMany
    {
        return $this->hasMany(SoundboardClip::class)->orderBy('sort_order');
    }

    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}



