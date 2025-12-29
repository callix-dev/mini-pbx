<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IvrNode extends Model
{
    use HasFactory;

    protected $fillable = [
        'ivr_id',
        'type',
        'digit',
        'audio_file_id',
        'destination_type',
        'destination_id',
        'time_conditions',
        'position_x',
        'position_y',
        'settings',
    ];

    protected $casts = [
        'time_conditions' => 'array',
        'settings' => 'array',
    ];

    public const TYPES = [
        'welcome' => 'Welcome Message',
        'menu' => 'Menu',
        'digit_route' => 'Digit Route',
        'time_condition' => 'Time Condition',
        'play_audio' => 'Play Audio',
        'hangup' => 'Hangup',
    ];

    public function ivr(): BelongsTo
    {
        return $this->belongsTo(Ivr::class);
    }

    public function audioFile(): BelongsTo
    {
        return $this->belongsTo(AudioFile::class);
    }

    public function outgoingConnections(): HasMany
    {
        return $this->hasMany(IvrNodeConnection::class, 'from_node_id');
    }

    public function incomingConnections(): HasMany
    {
        return $this->hasMany(IvrNodeConnection::class, 'to_node_id');
    }
}







