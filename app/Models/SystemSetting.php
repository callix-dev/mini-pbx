<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'description',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    public function getValueAttribute($value)
    {
        if ($this->is_encrypted && $value) {
            try {
                $value = Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }

        return match ($this->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    public function setValueAttribute($value)
    {
        if ($this->type === 'json' && is_array($value)) {
            $value = json_encode($value);
        }

        if ($this->is_encrypted && $value) {
            $value = Crypt::encryptString($value);
        }

        $this->attributes['value'] = $value;
    }

    public static function get(string $key, $default = null, ?string $group = null)
    {
        $cacheKey = "system_setting:{$group}:{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $default, $group) {
            $query = self::where('key', $key);
            
            if ($group) {
                $query->where('group', $group);
            }

            $setting = $query->first();
            
            return $setting ? $setting->value : $default;
        });
    }

    public static function set(string $key, $value, string $group = 'general', string $type = 'string', bool $encrypted = false): self
    {
        $setting = self::updateOrCreate(
            ['group' => $group, 'key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'is_encrypted' => $encrypted,
            ]
        );

        Cache::forget("system_setting:{$group}:{$key}");

        return $setting;
    }

    public static function getGroup(string $group): array
    {
        return self::where('group', $group)
            ->get()
            ->pluck('value', 'key')
            ->toArray();
    }

    public static function clearCache(?string $group = null, ?string $key = null): void
    {
        if ($group && $key) {
            Cache::forget("system_setting:{$group}:{$key}");
        } else {
            // Clear all system settings cache
            Cache::flush();
        }
    }
}

