<?php

namespace Database\Seeders;

use App\Models\CarrierTemplate;
use Illuminate\Database\Seeder;

class CarrierTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = $this->getTemplates();

        foreach ($templates as $template) {
            CarrierTemplate::updateOrCreate(
                [
                    'provider_slug' => $template['provider_slug'],
                    'direction' => $template['direction'],
                ],
                $template
            );
        }

        $this->command->info('Seeded ' . count($templates) . ' carrier templates.');
    }

    /**
     * Get all carrier templates
     */
    private function getTemplates(): array
    {
        return array_merge(
            $this->getTwilioTemplates(),
            $this->getTelnyxTemplates(),
            $this->getVonageTemplates(),
            $this->getRingCentralTemplates(),
            $this->getGenericRegistrationTemplates(),
            $this->getGenericIpTemplates(),
        );
    }

    /**
     * Twilio templates
     */
    private function getTwilioTemplates(): array
    {
        $baseConfig = [
            'provider_slug' => CarrierTemplate::PROVIDER_TWILIO,
            'provider_name' => 'Twilio',
            'logo_path' => 'images/carriers/twilio.svg',
            'regions' => [
                'us1' => ['host' => 'sip.us1.twilio.com', 'label' => 'US East (Virginia)'],
                'us2' => ['host' => 'sip.us2.twilio.com', 'label' => 'US West (Oregon)'],
                'ie1' => ['host' => 'sip.ie1.twilio.com', 'label' => 'Ireland'],
                'de1' => ['host' => 'sip.de1.twilio.com', 'label' => 'Germany'],
                'au1' => ['host' => 'sip.au1.twilio.com', 'label' => 'Australia'],
                'jp1' => ['host' => 'sip.jp1.twilio.com', 'label' => 'Japan'],
                'sg1' => ['host' => 'sip.sg1.twilio.com', 'label' => 'Singapore'],
                'br1' => ['host' => 'sip.br1.twilio.com', 'label' => 'Brazil'],
            ],
            'auth_types' => null, // Single auth type
            'provider_fields' => ['trunk_sid', 'credential_list_sid'],
            'help_links' => [
                'setup' => 'https://www.twilio.com/docs/sip-trunking',
                'credentials' => 'https://www.twilio.com/console/voice/sip-trunks',
                'regions' => 'https://www.twilio.com/docs/sip-trunking#termination-uris',
            ],
            'sort_order' => 1,
            'is_active' => true,
        ];

        return [
            array_merge($baseConfig, [
                'direction' => CarrierTemplate::DIRECTION_INBOUND,
                'description' => 'Receive calls from Twilio SIP Trunk. Configure your Twilio Termination URI to point to your PBX.',
                'default_config' => [
                    'host' => 'sip.us1.twilio.com',
                    'port' => 5060,
                    'transport' => 'udp',
                    'auth_type' => 'ip',
                    'context' => 'from-trunk',
                    'codecs' => ['ulaw', 'alaw', 'g722', 'opus'],
                ],
                'required_fields' => [],
            ]),
            array_merge($baseConfig, [
                'direction' => CarrierTemplate::DIRECTION_OUTBOUND,
                'description' => 'Make outbound calls through Twilio Elastic SIP Trunking. Requires Credential List for authentication.',
                'default_config' => [
                    'host' => 'sip.us1.twilio.com',
                    'port' => 5060,
                    'transport' => 'udp',
                    'auth_type' => 'registration',
                    'context' => 'from-internal',
                    'codecs' => ['ulaw', 'alaw', 'g722', 'opus'],
                ],
                'required_fields' => ['username', 'password'],
            ]),
        ];
    }

    /**
     * Telnyx templates
     */
    private function getTelnyxTemplates(): array
    {
        $baseConfig = [
            'provider_slug' => CarrierTemplate::PROVIDER_TELNYX,
            'provider_name' => 'Telnyx',
            'logo_path' => 'images/carriers/telnyx.svg',
            'regions' => [
                'global' => ['host' => 'sip.telnyx.com', 'label' => 'Global (Anycast)'],
                'us' => ['host' => 'sip.us.telnyx.com', 'label' => 'United States'],
                'eu' => ['host' => 'sip.eu.telnyx.com', 'label' => 'Europe'],
            ],
            'auth_types' => ['credentials', 'ip'],
            'provider_fields' => ['connection_id'],
            'help_links' => [
                'setup' => 'https://developers.telnyx.com/docs/voice/sip-trunking',
                'credentials' => 'https://portal.telnyx.com/#/app/connections',
                'connection_id' => 'https://portal.telnyx.com/#/app/connections',
            ],
            'sort_order' => 2,
            'is_active' => true,
        ];

        return [
            array_merge($baseConfig, [
                'direction' => CarrierTemplate::DIRECTION_INBOUND,
                'description' => 'Receive calls from Telnyx. Supports both Credential and IP-based authentication.',
                'default_config' => [
                    'host' => 'sip.telnyx.com',
                    'port' => 5060,
                    'transport' => 'udp',
                    'auth_type' => 'ip',
                    'context' => 'from-trunk',
                    'codecs' => ['ulaw', 'alaw', 'g722', 'opus'],
                ],
                'required_fields' => [
                    'credentials' => ['username', 'password'],
                    'ip' => [],
                ],
            ]),
            array_merge($baseConfig, [
                'direction' => CarrierTemplate::DIRECTION_OUTBOUND,
                'description' => 'Make outbound calls through Telnyx. Supports Credentials or IP authentication.',
                'default_config' => [
                    'host' => 'sip.telnyx.com',
                    'port' => 5060,
                    'transport' => 'udp',
                    'auth_type' => 'registration',
                    'context' => 'from-internal',
                    'codecs' => ['ulaw', 'alaw', 'g722', 'opus'],
                ],
                'required_fields' => [
                    'credentials' => ['username', 'password'],
                    'ip' => [],
                ],
            ]),
        ];
    }

    /**
     * Vonage templates
     */
    private function getVonageTemplates(): array
    {
        $baseConfig = [
            'provider_slug' => CarrierTemplate::PROVIDER_VONAGE,
            'provider_name' => 'Vonage',
            'logo_path' => 'images/carriers/vonage.svg',
            'regions' => [
                'us' => ['host' => 'sip-us.nexmo.com', 'label' => 'United States'],
                'eu' => ['host' => 'sip-eu.nexmo.com', 'label' => 'Europe'],
                'apac' => ['host' => 'sip-ap.nexmo.com', 'label' => 'Asia Pacific'],
            ],
            'auth_types' => null, // IP auth only
            'provider_fields' => ['api_key', 'api_secret'],
            'help_links' => [
                'setup' => 'https://developer.vonage.com/en/voice/sip/overview',
                'credentials' => 'https://dashboard.nexmo.com/settings',
                'ip_acl' => 'https://dashboard.nexmo.com/sip/your-numbers',
            ],
            'sort_order' => 3,
            'is_active' => true,
        ];

        return [
            array_merge($baseConfig, [
                'direction' => CarrierTemplate::DIRECTION_INBOUND,
                'description' => 'Receive calls from Vonage (Nexmo). Uses IP-based authentication - whitelist your PBX IP in Vonage dashboard.',
                'default_config' => [
                    'host' => 'sip-us.nexmo.com',
                    'port' => 5060,
                    'transport' => 'udp',
                    'auth_type' => 'ip',
                    'context' => 'from-trunk',
                    'codecs' => ['ulaw', 'alaw'],
                ],
                'required_fields' => [],
            ]),
            array_merge($baseConfig, [
                'direction' => CarrierTemplate::DIRECTION_OUTBOUND,
                'description' => 'Make outbound calls through Vonage (Nexmo). Configure your PBX IP in Vonage for IP authentication.',
                'default_config' => [
                    'host' => 'sip-us.nexmo.com',
                    'port' => 5060,
                    'transport' => 'udp',
                    'auth_type' => 'ip',
                    'context' => 'from-internal',
                    'codecs' => ['ulaw', 'alaw'],
                ],
                'required_fields' => [],
            ]),
        ];
    }

    /**
     * RingCentral templates
     */
    private function getRingCentralTemplates(): array
    {
        $baseConfig = [
            'provider_slug' => CarrierTemplate::PROVIDER_RINGCENTRAL,
            'provider_name' => 'RingCentral',
            'logo_path' => 'images/carriers/ringcentral.svg',
            'regions' => [
                'us' => [
                    'host' => 'sip.ringcentral.com',
                    'label' => 'United States',
                    'outbound_proxy' => 'sip10.ringcentral.com:5090',
                ],
            ],
            'auth_types' => ['credentials', 'ip'],
            'provider_fields' => ['authorization_id', 'outbound_proxy'],
            'help_links' => [
                'setup' => 'https://support.ringcentral.com/article/Using-SIP-Registered-Phones.html',
                'credentials' => 'https://service.ringcentral.com/application/settings/devices',
                'proxy' => 'https://support.ringcentral.com/article/SIP-Server-settings.html',
            ],
            'sort_order' => 4,
            'is_active' => true,
        ];

        return [
            array_merge($baseConfig, [
                'direction' => CarrierTemplate::DIRECTION_INBOUND,
                'description' => 'Receive calls from RingCentral. Supports Credential-based or IP-based authentication.',
                'default_config' => [
                    'host' => 'sip.ringcentral.com',
                    'port' => 5060,
                    'transport' => 'udp',
                    'auth_type' => 'registration',
                    'context' => 'from-trunk',
                    'codecs' => ['ulaw', 'alaw', 'g722'],
                ],
                'required_fields' => [
                    'credentials' => ['username', 'password'],
                    'ip' => [],
                ],
            ]),
            array_merge($baseConfig, [
                'direction' => CarrierTemplate::DIRECTION_OUTBOUND,
                'description' => 'Make outbound calls through RingCentral. Requires outbound proxy configuration.',
                'default_config' => [
                    'host' => 'sip.ringcentral.com',
                    'port' => 5060,
                    'transport' => 'udp',
                    'auth_type' => 'registration',
                    'context' => 'from-internal',
                    'codecs' => ['ulaw', 'alaw', 'g722'],
                    'outbound_proxy' => 'sip10.ringcentral.com:5090',
                ],
                'required_fields' => [
                    'credentials' => ['username', 'password', 'authorization_id'],
                    'ip' => [],
                ],
            ]),
        ];
    }

    /**
     * Generic Registration templates
     */
    private function getGenericRegistrationTemplates(): array
    {
        $baseConfig = [
            'provider_slug' => CarrierTemplate::PROVIDER_GENERIC_REGISTRATION,
            'provider_name' => 'Generic (Registration)',
            'logo_path' => 'images/carriers/generic-registration.svg',
            'regions' => null,
            'auth_types' => null,
            'provider_fields' => null,
            'help_links' => [
                'asterisk' => 'https://wiki.asterisk.org/wiki/display/AST/PJSIP+Configuration+Wizard',
            ],
            'sort_order' => 10,
            'is_active' => true,
        ];

        return [
            array_merge($baseConfig, [
                'direction' => CarrierTemplate::DIRECTION_INBOUND,
                'description' => 'Generic SIP trunk with username/password authentication for inbound calls.',
                'default_config' => [
                    'host' => '',
                    'port' => 5060,
                    'transport' => 'udp',
                    'auth_type' => 'registration',
                    'context' => 'from-trunk',
                    'codecs' => ['ulaw', 'alaw', 'g722', 'opus'],
                ],
                'required_fields' => ['host', 'username', 'password'],
            ]),
            array_merge($baseConfig, [
                'direction' => CarrierTemplate::DIRECTION_OUTBOUND,
                'description' => 'Generic SIP trunk with username/password authentication for outbound calls.',
                'default_config' => [
                    'host' => '',
                    'port' => 5060,
                    'transport' => 'udp',
                    'auth_type' => 'registration',
                    'context' => 'from-internal',
                    'codecs' => ['ulaw', 'alaw', 'g722', 'opus'],
                ],
                'required_fields' => ['host', 'username', 'password'],
            ]),
        ];
    }

    /**
     * Generic IP Auth templates
     */
    private function getGenericIpTemplates(): array
    {
        $baseConfig = [
            'provider_slug' => CarrierTemplate::PROVIDER_GENERIC_IP,
            'provider_name' => 'Generic (IP Auth)',
            'logo_path' => 'images/carriers/generic-ip.svg',
            'regions' => null,
            'auth_types' => null,
            'provider_fields' => ['allowed_ips'],
            'help_links' => [
                'asterisk' => 'https://wiki.asterisk.org/wiki/display/AST/PJSIP+Configuration+Wizard',
            ],
            'sort_order' => 11,
            'is_active' => true,
        ];

        return [
            array_merge($baseConfig, [
                'direction' => CarrierTemplate::DIRECTION_INBOUND,
                'description' => 'Generic SIP trunk with IP-based authentication for inbound calls. No credentials required.',
                'default_config' => [
                    'host' => '',
                    'port' => 5060,
                    'transport' => 'udp',
                    'auth_type' => 'ip',
                    'context' => 'from-trunk',
                    'codecs' => ['ulaw', 'alaw', 'g722', 'opus'],
                ],
                'required_fields' => ['host'],
            ]),
            array_merge($baseConfig, [
                'direction' => CarrierTemplate::DIRECTION_OUTBOUND,
                'description' => 'Generic SIP trunk with IP-based authentication for outbound calls.',
                'default_config' => [
                    'host' => '',
                    'port' => 5060,
                    'transport' => 'udp',
                    'auth_type' => 'ip',
                    'context' => 'from-internal',
                    'codecs' => ['ulaw', 'alaw', 'g722', 'opus'],
                ],
                'required_fields' => ['host'],
            ]),
        ];
    }
}

