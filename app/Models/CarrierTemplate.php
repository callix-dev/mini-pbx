<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarrierTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_slug',
        'provider_name',
        'logo_path',
        'direction',
        'default_config',
        'regions',
        'auth_types',
        'required_fields',
        'provider_fields',
        'help_links',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'default_config' => 'array',
        'regions' => 'array',
        'auth_types' => 'array',
        'required_fields' => 'array',
        'provider_fields' => 'array',
        'help_links' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Provider slugs
     */
    public const PROVIDER_TWILIO = 'twilio';
    public const PROVIDER_TELNYX = 'telnyx';
    public const PROVIDER_VONAGE = 'vonage';
    public const PROVIDER_RINGCENTRAL = 'ringcentral';
    public const PROVIDER_GENERIC_REGISTRATION = 'generic_registration';
    public const PROVIDER_GENERIC_IP = 'generic_ip';

    /**
     * Direction types
     */
    public const DIRECTION_INBOUND = 'inbound';
    public const DIRECTION_OUTBOUND = 'outbound';

    /**
     * Get all providers with their display names
     */
    public static function getProviders(): array
    {
        return [
            self::PROVIDER_TWILIO => 'Twilio',
            self::PROVIDER_TELNYX => 'Telnyx',
            self::PROVIDER_VONAGE => 'Vonage',
            self::PROVIDER_RINGCENTRAL => 'RingCentral',
            self::PROVIDER_GENERIC_REGISTRATION => 'Generic (Registration)',
            self::PROVIDER_GENERIC_IP => 'Generic (IP Auth)',
        ];
    }

    /**
     * Scope: Active templates only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by direction
     */
    public function scopeDirection($query, string $direction)
    {
        return $query->where('direction', $direction);
    }

    /**
     * Scope: Filter by provider
     */
    public function scopeProvider($query, string $providerSlug)
    {
        return $query->where('provider_slug', $providerSlug);
    }

    /**
     * Get template by provider and direction
     */
    public static function getTemplate(string $providerSlug, string $direction): ?self
    {
        return static::active()
            ->provider($providerSlug)
            ->direction($direction)
            ->first();
    }

    /**
     * Get all templates grouped by provider
     */
    public static function getGroupedByProvider(): array
    {
        $templates = static::active()
            ->orderBy('sort_order')
            ->orderBy('provider_name')
            ->get();

        $grouped = [];
        foreach ($templates as $template) {
            if (!isset($grouped[$template->provider_slug])) {
                $grouped[$template->provider_slug] = [
                    'name' => $template->provider_name,
                    'logo' => $template->logo_path,
                    'templates' => [],
                ];
            }
            $grouped[$template->provider_slug]['templates'][$template->direction] = $template;
        }

        return $grouped;
    }

    /**
     * Check if this provider supports multiple auth types
     */
    public function hasMultipleAuthTypes(): bool
    {
        return is_array($this->auth_types) && count($this->auth_types) > 1;
    }

    /**
     * Get the default auth type
     */
    public function getDefaultAuthType(): ?string
    {
        if (is_array($this->auth_types) && !empty($this->auth_types)) {
            return $this->auth_types[0];
        }
        return $this->default_config['auth_type'] ?? null;
    }

    /**
     * Get required fields for a specific auth type
     */
    public function getRequiredFieldsForAuthType(?string $authType = null): array
    {
        $requiredFields = $this->required_fields ?? [];

        // If required_fields is keyed by auth type
        if ($authType && isset($requiredFields[$authType])) {
            return $requiredFields[$authType];
        }

        // If it's a flat array
        if (!empty($requiredFields) && !isset($requiredFields['credentials']) && !isset($requiredFields['ip'])) {
            return $requiredFields;
        }

        return $requiredFields['credentials'] ?? $requiredFields;
    }

    /**
     * Get the logo URL
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }
        return asset($this->logo_path);
    }

    /**
     * Generate a unique trunk name for this provider
     */
    public function generateTrunkName(): string
    {
        $count = Carrier::where('provider_slug', $this->provider_slug)
            ->where('type', $this->direction)
            ->count();

        return $this->provider_slug . '_' . $this->direction . '_' . ($count + 1);
    }
}

