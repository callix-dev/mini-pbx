<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'file_path',
        'file_size',
        'type',
        'included_tables',
        'status',
        'notes',
        'created_by',
        'completed_at',
    ];

    protected $casts = [
        'included_tables' => 'array',
        'completed_at' => 'datetime',
    ];

    public const TYPES = [
        'manual' => 'Manual',
        'scheduled' => 'Scheduled',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'failed' => 'Failed',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 2) . ' ' . $units[$index];
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeManual($query)
    {
        return $query->where('type', 'manual');
    }

    public function scopeScheduled($query)
    {
        return $query->where('type', 'scheduled');
    }
}







