<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Settings Service
 * 
 * Provides a unified way to access settings with database overriding ENV.
 * Database values take priority, with ENV/config as fallback.
 */
class SettingsService
{
    /**
     * Cache TTL in seconds
     */
    protected const CACHE_TTL = 3600;

    /**
     * Get a setting value.
     * Priority: Database -> ENV/Config -> Default
     */
    public static function get(string $key, $default = null)
    {
        // Try database first
        $dbValue = self::getFromDatabase($key);
        
        if ($dbValue !== null && $dbValue !== '') {
            return $dbValue;
        }

        // Fall back to config (which reads from ENV)
        $configKey = self::getConfigKey($key);
        if ($configKey) {
            $configValue = config($configKey);
            if ($configValue !== null) {
                return $configValue;
            }
        }

        return $default;
    }

    /**
     * Get value from database with caching
     */
    protected static function getFromDatabase(string $key)
    {
        $cacheKey = "settings_service:{$key}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key) {
            try {
                $setting = SystemSetting::where('key', $key)->first();
                return $setting?->value;
            } catch (\Exception $e) {
                // Database might not be available during early boot
                return null;
            }
        });
    }

    /**
     * Map setting keys to config keys
     */
    protected static function getConfigKey(string $key): ?string
    {
        $map = [
            // AMI Settings
            'ami_host' => 'asterisk.ami.host',
            'ami_port' => 'asterisk.ami.port',
            'ami_username' => 'asterisk.ami.username',
            'ami_secret' => 'asterisk.ami.password',

            // ARI Settings
            'ari_url' => null, // Composed from host/port
            'ari_app' => 'asterisk.ari.app_name',
            'ari_username' => 'asterisk.ari.username',
            'ari_password' => 'asterisk.ari.password',

            // WebRTC Settings
            'stun_server' => 'webphone.stun_server',
            'turn_server' => 'webphone.turn_server',
            'turn_username' => 'webphone.turn_username',
            'turn_credential' => 'webphone.turn_credential',

            // General Settings
            'timezone' => 'app.timezone',
            'session_timeout' => 'session.lifetime',
        ];

        return $map[$key] ?? null;
    }

    /**
     * Get all AMI settings
     */
    public static function getAmiSettings(): array
    {
        return [
            'host' => self::get('ami_host', '127.0.0.1'),
            'port' => (int) self::get('ami_port', 5038),
            'username' => self::get('ami_username', 'admin'),
            'password' => self::get('ami_secret', ''),
            'connect_timeout' => (int) config('asterisk.ami.connect_timeout', 10),
            'read_timeout' => (int) config('asterisk.ami.read_timeout', 10),
        ];
    }

    /**
     * Get all ARI settings
     */
    public static function getAriSettings(): array
    {
        $url = self::get('ari_url');
        
        // Parse URL or use config defaults
        if ($url) {
            $parsed = parse_url($url);
            $host = $parsed['host'] ?? '127.0.0.1';
            $port = $parsed['port'] ?? 8088;
            $ssl = ($parsed['scheme'] ?? 'http') === 'https';
        } else {
            $host = config('asterisk.ari.host', '127.0.0.1');
            $port = config('asterisk.ari.port', 8088);
            $ssl = config('asterisk.ari.ssl', false);
        }

        return [
            'host' => $host,
            'port' => (int) $port,
            'username' => self::get('ari_username', config('asterisk.ari.username', 'admin')),
            'password' => self::get('ari_password', config('asterisk.ari.password', '')),
            'app_name' => self::get('ari_app', config('asterisk.ari.app_name', 'mini-pbx')),
            'ssl' => $ssl,
            'url' => $url ?? "http://{$host}:{$port}/ari",
        ];
    }

    /**
     * Get all WebRTC settings
     */
    public static function getWebRtcSettings(): array
    {
        return [
            'stun_server' => self::get('stun_server', config('webphone.stun_server', 'stun:stun.l.google.com:19302')),
            'turn_server' => self::get('turn_server', config('webphone.turn_server')),
            'turn_username' => self::get('turn_username', config('webphone.turn_username')),
            'turn_credential' => self::get('turn_credential', config('webphone.turn_credential')),
        ];
    }

    /**
     * Get email/SMTP settings
     */
    public static function getEmailSettings(): array
    {
        return [
            'host' => self::get('smtp_host', config('mail.mailers.smtp.host')),
            'port' => (int) self::get('smtp_port', config('mail.mailers.smtp.port', 587)),
            'username' => self::get('smtp_username', config('mail.mailers.smtp.username')),
            'password' => self::get('smtp_password', config('mail.mailers.smtp.password')),
            'encryption' => self::get('smtp_encryption', config('mail.mailers.smtp.encryption', 'tls')),
            'from_address' => self::get('mail_from_address', config('mail.from.address')),
            'from_name' => self::get('mail_from_name', config('mail.from.name')),
        ];
    }

    /**
     * Clear settings cache
     */
    public static function clearCache(?string $key = null): void
    {
        if ($key) {
            Cache::forget("settings_service:{$key}");
        } else {
            // Clear all settings cache - in production use tagged cache
            $keys = [
                'ami_host', 'ami_port', 'ami_username', 'ami_secret',
                'ari_url', 'ari_app', 'ari_username', 'ari_password',
                'stun_server', 'turn_server', 'turn_username', 'turn_credential',
                'timezone', 'session_timeout', 'data_retention_days', 'recording_retention_days',
                'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password',
                'smtp_encryption', 'mail_from_address', 'mail_from_name',
            ];

            foreach ($keys as $k) {
                Cache::forget("settings_service:{$k}");
            }
        }
    }
}





