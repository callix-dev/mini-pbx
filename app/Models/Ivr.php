<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ivr extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'timeout',
        'invalid_retries',
        'invalid_destination_type',
        'invalid_destination_id',
        'timeout_destination_type',
        'timeout_destination_id',
        'direct_dial',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'direct_dial' => 'boolean',
        'settings' => 'array',
    ];

    public function nodes(): HasMany
    {
        return $this->hasMany(IvrNode::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}



