<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExtensionGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'ring_strategy',
        'ring_time',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public const RING_STRATEGIES = [
        'ringall' => 'Ring All',
        'hunt' => 'Hunt (Linear)',
        'memoryhunt' => 'Memory Hunt',
        'leastrecent' => 'Least Recent',
        'fewestcalls' => 'Fewest Calls',
        'random' => 'Random',
    ];

    public function extensions(): BelongsToMany
    {
        return $this->belongsToMany(Extension::class)
            ->withPivot('priority')
            ->withTimestamps()
            ->orderByPivot('priority');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getRingStrategyLabelAttribute(): string
    {
        return self::RING_STRATEGIES[$this->ring_strategy] ?? $this->ring_strategy;
    }
}


