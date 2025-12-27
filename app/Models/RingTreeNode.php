<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RingTreeNode extends Model
{
    use HasFactory;

    protected $fillable = [
        'ring_tree_id',
        'parent_id',
        'level',
        'position',
        'destination_type',
        'destination_id',
        'timeout',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public const DESTINATION_TYPES = [
        'extension' => 'Extension',
        'extension_group' => 'Extension Group',
        'queue' => 'Queue',
        'hangup' => 'Hangup',
        'voicemail' => 'Voicemail',
        'block_filter' => 'Block Filter',
    ];

    public function ringTree(): BelongsTo
    {
        return $this->belongsTo(RingTree::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(RingTreeNode::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(RingTreeNode::class, 'parent_id')->orderBy('position');
    }

    public function getDestinationModelAttribute()
    {
        if (!$this->destination_type || !$this->destination_id) {
            return null;
        }

        $modelClass = $this->getDestinationClass($this->destination_type);
        return $modelClass ? $modelClass::find($this->destination_id) : null;
    }

    protected function getDestinationClass(string $type): ?string
    {
        return match ($type) {
            'extension' => Extension::class,
            'extension_group' => ExtensionGroup::class,
            'queue' => Queue::class,
            default => null,
        };
    }
}



