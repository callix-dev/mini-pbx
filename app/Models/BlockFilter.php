<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockFilter extends Model
{
    use HasFactory;

    protected $fillable = [
        'block_filter_group_id',
        'type',
        'pattern',
        'match_type',
        'name',
        'description',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'date',
    ];

    public const TYPES = [
        'blacklist' => 'Blacklist',
        'whitelist' => 'Whitelist',
    ];

    public const MATCH_TYPES = [
        'exact' => 'Exact Match',
        'prefix' => 'Prefix Match',
        'regex' => 'Regular Expression',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(BlockFilterGroup::class, 'block_filter_group_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeMatchesNumber($query, string $number)
    {
        return $query->where(function ($q) use ($number) {
            $q->where(function ($q2) use ($number) {
                $q2->where('match_type', 'exact')
                    ->where('pattern', $number);
            })->orWhere(function ($q2) use ($number) {
                $q2->where('match_type', 'prefix')
                    ->whereRaw('? LIKE CONCAT(pattern, \'%\')', [$number]);
            });
            // Note: Regex matching should be done in PHP for security
        });
    }

    public function matchesNumber(string $number): bool
    {
        return match ($this->match_type) {
            'exact' => $this->pattern === $number,
            'prefix' => str_starts_with($number, $this->pattern),
            'regex' => (bool)preg_match('/' . $this->pattern . '/', $number),
            default => false,
        };
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}





