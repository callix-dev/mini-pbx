<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\AuditLog;
use App\Services\Asterisk\AmiService;
use App\Services\Asterisk\AriService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function index(): View
    {
        $settings = [
            'ami' => SystemSetting::getGroup('ami'),
            'ari' => SystemSetting::getGroup('ari'),
            'general' => SystemSetting::getGroup('general'),
            'webrtc' => SystemSetting::getGroup('webrtc'),
            'email' => SystemSetting::getGroup('email'),
            'retention' => SystemSetting::getGroup('retention'),
        ];

        $timezones = \DateTimeZone::listIdentifiers();

        return view('platform.system-settings.index', compact('settings', 'timezones'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // AMI Settings
            'ami.host' => 'nullable|string|max:255',
            'ami.port' => 'nullable|integer|min:1|max:65535',
            'ami.username' => 'nullable|string|max:255',
            'ami.password' => 'nullable|string|max:255',
            'ami.enabled' => 'nullable|boolean',

            // ARI Settings
            'ari.host' => 'nullable|string|max:255',
            'ari.port' => 'nullable|integer|min:1|max:65535',
            'ari.username' => 'nullable|string|max:255',
            'ari.password' => 'nullable|string|max:255',
            'ari.enabled' => 'nullable|boolean',

            // General Settings
            'general.timezone' => 'nullable|string|max:50',
            'general.asterisk_config_path' => 'nullable|string|max:255',
            'general.asterisk_sounds_path' => 'nullable|string|max:255',
            'general.asterisk_recording_path' => 'nullable|string|max:255',

            // WebRTC Settings
            'webrtc.stun_server' => 'nullable|string|max:255',
            'webrtc.turn_server' => 'nullable|string|max:255',
            'webrtc.turn_username' => 'nullable|string|max:255',
            'webrtc.turn_password' => 'nullable|string|max:255',

            // Email Settings
            'email.host' => 'nullable|string|max:255',
            'email.port' => 'nullable|integer|min:1|max:65535',
            'email.username' => 'nullable|string|max:255',
            'email.password' => 'nullable|string|max:255',
            'email.encryption' => 'nullable|in:tls,ssl,null',
            'email.from_address' => 'nullable|email',
            'email.from_name' => 'nullable|string|max:255',

            // Retention Settings
            'retention.enabled' => 'nullable|boolean',
            'retention.days' => 'nullable|integer|min:1|max:3650',
        ]);

        foreach ($validated as $group => $settings) {
            if (is_array($settings)) {
                foreach ($settings as $key => $value) {
                    $isEncrypted = in_array($key, ['password']);
                    $type = is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : 'string');

                    SystemSetting::set($key, $value, $group, $type, $isEncrypted);
                }
            }
        }

        AuditLog::log('settings_changed', null, null, null, 'System settings updated');

        return redirect()->back()
            ->with('success', 'System settings updated successfully.');
    }

    public function testAmi(): JsonResponse
    {
        try {
            $ami = app(AmiService::class);
            $connected = $ami->testConnection();

            return response()->json([
                'success' => $connected,
                'message' => $connected ? 'AMI connection successful' : 'AMI connection failed',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'AMI connection failed: ' . $e->getMessage(),
            ]);
        }
    }

    public function testAri(): JsonResponse
    {
        try {
            $ari = app(AriService::class);
            $connected = $ari->testConnection();

            return response()->json([
                'success' => $connected,
                'message' => $connected ? 'ARI connection successful' : 'ARI connection failed',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ARI connection failed: ' . $e->getMessage(),
            ]);
        }
    }
}

