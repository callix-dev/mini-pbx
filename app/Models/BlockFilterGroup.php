<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlockFilterGroup extends Model
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

    public function filters(): HasMany
    {
        return $this->hasMany(BlockFilter::class);
    }

    public function blacklist(): HasMany
    {
        return $this->hasMany(BlockFilter::class)->where('type', 'blacklist');
    }

    public function whitelist(): HasMany
    {
        return $this->hasMany(BlockFilter::class)->where('type', 'whitelist');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isBlocked(string $number): bool
    {
        // Check whitelist first - if whitelisted, not blocked
        if ($this->whitelist()->active()->matchesNumber($number)->exists()) {
            return false;
        }

        // Check blacklist
        return $this->blacklist()->active()->matchesNumber($number)->exists();
    }
}


