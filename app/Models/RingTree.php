<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RingTree extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public function nodes(): HasMany
    {
        return $this->hasMany(RingTreeNode::class)->orderBy('level')->orderBy('position');
    }

    public function rootNodes(): HasMany
    {
        return $this->hasMany(RingTreeNode::class)->whereNull('parent_id')->orderBy('position');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}







