<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoicemailGreeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'extension_id',
        'type',
        'file_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const TYPES = [
        'unavailable' => 'Unavailable',
        'busy' => 'Busy',
        'temp' => 'Temporary',
        'name' => 'Name Recording',
    ];

    public function extension(): BelongsTo
    {
        return $this->belongsTo(Extension::class);
    }
}



