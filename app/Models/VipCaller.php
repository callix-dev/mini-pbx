<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VipCaller extends Model
{
    use HasFactory;

    protected $fillable = [
        'queue_id',
        'caller_id',
        'name',
        'priority',
    ];

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }
}



