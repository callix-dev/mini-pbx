<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Did extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number',
        'name',
        'description',
        'carrier_id',
        'destination_type',
        'destination_id',
        'after_hours_destination_type',
        'after_hours_destination_id',
        'block_filter_group_id',
        'is_active',
        'time_based_routing',
        'business_hours',
        'caller_id_routing',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'time_based_routing' => 'boolean',
        'business_hours' => 'array',
        'caller_id_routing' => 'array',
        'settings' => 'array',
    ];

    public const DESTINATION_TYPES = [
        'extension' => 'Extension',
        'extension_group' => 'Extension Group',
        'queue' => 'Queue',
        'ring_tree' => 'Ring Tree',
        'ivr' => 'IVR',
        'voicemail' => 'Voicemail',
        'external' => 'External Number',
    ];

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function blockFilterGroup(): BelongsTo
    {
        return $this->belongsTo(BlockFilterGroup::class);
    }

    public function destination(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'destination_type', 'destination_id');
    }

    public function afterHoursDestination(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'after_hours_destination_type', 'after_hours_destination_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
            'ring_tree' => RingTree::class,
            'ivr' => Ivr::class,
            default => null,
        };
    }
}





