<?php

namespace App\Services\Carrier;

use App\Models\Carrier;
use App\Models\CarrierTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CarrierTemplateService
{
    /**
     * Get all active templates
     */
    public function getTemplates(?string $direction = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = CarrierTemplate::active()
            ->orderBy('sort_order')
            ->orderBy('provider_name');

        if ($direction) {
            $query->direction($direction);
        }

        return $query->get();
    }

    /**
     * Get templates grouped by provider for display
     */
    public function getTemplatesGroupedByProvider(?string $direction = null): array
    {
        $templates = $this->getTemplates($direction);

        $grouped = [];
        foreach ($templates as $template) {
            if (!isset($grouped[$template->provider_slug])) {
                $grouped[$template->provider_slug] = [
                    'slug' => $template->provider_slug,
                    'name' => $template->provider_name,
                    'logo' => $template->logo_url,
                    'templates' => [],
                ];
            }
            $grouped[$template->provider_slug]['templates'][$template->direction] = $template;
        }

        return $grouped;
    }

    /**
     * Get a specific template
     */
    public function getTemplate(string $providerSlug, string $direction): ?CarrierTemplate
    {
        return CarrierTemplate::getTemplate($providerSlug, $direction);
    }

    /**
     * Apply a template with user input to create carrier config
     */
    public function applyTemplate(CarrierTemplate $template, array $userInput): array
    {
        $config = $template->default_config ?? [];

        // Apply region if selected
        if (!empty($userInput['region']) && !empty($template->regions)) {
            $region = $template->regions[$userInput['region']] ?? null;
            if ($region) {
                $config['host'] = $region['host'];
                
                // Apply region-specific settings (like outbound_proxy for RingCentral)
                if (isset($region['outbound_proxy'])) {
                    $config['outbound_proxy'] = $region['outbound_proxy'];
                }
            }
        }

        // Apply auth type if provider supports multiple
        if (!empty($userInput['auth_type']) && $template->hasMultipleAuthTypes()) {
            $config['auth_type'] = $userInput['auth_type'] === 'credentials' ? 'registration' : 'ip';
        }

        // Merge user input (overrides template defaults)
        $mergeFields = ['host', 'port', 'transport', 'username', 'password', 'from_domain', 'from_user', 'codecs', 'max_channels', 'context'];
        foreach ($mergeFields as $field) {
            if (isset($userInput[$field]) && $userInput[$field] !== '') {
                $config[$field] = $userInput[$field];
            }
        }
        
        // For credentials auth with from_domain, ensure from_domain is set
        if (!empty($userInput['from_domain'])) {
            $config['from_domain'] = $userInput['from_domain'];
        }

        // Build provider_config from provider-specific fields
        $providerConfig = [];
        if ($template->provider_fields) {
            foreach ($template->provider_fields as $field) {
                if (isset($userInput[$field]) && $userInput[$field] !== '') {
                    $providerConfig[$field] = $userInput[$field];
                }
            }
        }

        // Add outbound_proxy to provider_config if set
        if (isset($config['outbound_proxy'])) {
            $providerConfig['outbound_proxy'] = $config['outbound_proxy'];
            unset($config['outbound_proxy']);
        }

        return [
            'config' => $config,
            'provider_config' => $providerConfig,
        ];
    }

    /**
     * Generate a unique trunk name for a provider
     */
    public function generateTrunkName(string $providerSlug, string $direction): string
    {
        $count = Carrier::where('provider_slug', $providerSlug)
            ->where('type', $direction)
            ->count();

        return $providerSlug . '_' . $direction . '_' . ($count + 1);
    }

    /**
     * Create a carrier from a template
     */
    public function createCarrierFromTemplate(
        CarrierTemplate $template,
        array $userInput,
        ?string $customName = null
    ): Carrier {
        $applied = $this->applyTemplate($template, $userInput);
        $config = $applied['config'];
        $providerConfig = $applied['provider_config'];

        // Generate name if not provided
        $name = $customName ?: $this->generateTrunkName($template->provider_slug, $template->direction);

        try {
            DB::beginTransaction();

            $carrier = Carrier::create([
                'name' => $name,
                'provider_slug' => $template->provider_slug,
                'type' => $template->direction,
                'technology' => 'pjsip',
                'host' => $config['host'] ?? '',
                'port' => $config['port'] ?? 5060,
                'transport' => $config['transport'] ?? 'udp',
                'auth_type' => $config['auth_type'] ?? 'registration',
                'username' => $config['username'] ?? null,
                'password' => $config['password'] ?? null,
                'from_domain' => $config['from_domain'] ?? null,
                'from_user' => $config['from_user'] ?? null,
                'codecs' => $config['codecs'] ?? Carrier::DEFAULT_CODECS,
                'max_channels' => $config['max_channels'] ?? null,
                'context' => $config['context'] ?? 'from-trunk',
                'is_active' => true,
                'priority' => 0,
                'provider_config' => !empty($providerConfig) ? $providerConfig : null,
            ]);

            DB::commit();

            Log::info('Carrier created from template', [
                'carrier_id' => $carrier->id,
                'carrier_name' => $carrier->name,
                'provider' => $template->provider_slug,
                'direction' => $template->direction,
            ]);

            return $carrier;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create carrier from template', [
                'provider' => $template->provider_slug,
                'direction' => $template->direction,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Validate user input against template requirements
     */
    public function validateInput(CarrierTemplate $template, array $input, ?string $authType = null): array
    {
        $errors = [];
        $requiredFields = $template->getRequiredFieldsForAuthType($authType);

        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }

        // Validate host format
        if (!empty($input['host'])) {
            if (!filter_var($input['host'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) &&
                !filter_var($input['host'], FILTER_VALIDATE_IP)) {
                $errors['host'] = 'Invalid host format.';
            }
        }

        // Validate port
        if (!empty($input['port'])) {
            $port = (int) $input['port'];
            if ($port < 1 || $port > 65535) {
                $errors['port'] = 'Port must be between 1 and 65535.';
            }
        }

        return $errors;
    }

    /**
     * Get help text for a field
     */
    public function getFieldHelp(CarrierTemplate $template, string $field): ?string
    {
        $helpTexts = [
            'username' => 'Your SIP username or account ID provided by ' . $template->provider_name,
            'password' => 'Your SIP password or secret',
            'host' => 'SIP server hostname or IP address',
            'port' => 'SIP port (usually 5060 for UDP/TCP, 5061 for TLS)',
            'from_domain' => 'The SIP domain for authentication (provided by your carrier)',
            'authorization_id' => 'Authorization ID (may be same as username for some providers)',
            'outbound_proxy' => 'Outbound proxy server for SIP signaling',
            'trunk_sid' => 'Your Twilio Trunk SID (starts with TK)',
            'credential_list_sid' => 'Your Twilio Credential List SID (starts with CL)',
            'connection_id' => 'Your Telnyx Connection ID from the portal',
            'api_key' => 'Your API key from the provider dashboard',
        ];

        return $helpTexts[$field] ?? null;
    }
}

