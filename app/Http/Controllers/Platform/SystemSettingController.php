<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\AuditLog;
use App\Services\Asterisk\AmiService;
use App\Services\Asterisk\AriService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function index(): View
    {
        // Get all settings as a flat array for the view
        $settings = SystemSetting::all()->pluck('value', 'key')->toArray();

        return view('platform.system-settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // AMI Settings
            'ami_host' => 'nullable|string|max:255',
            'ami_port' => 'nullable|integer|min:1|max:65535',
            'ami_username' => 'nullable|string|max:255',
            'ami_secret' => 'nullable|string|max:255',

            // ARI Settings
            'ari_url' => 'nullable|string|max:255',
            'ari_app' => 'nullable|string|max:255',
            'ari_username' => 'nullable|string|max:255',
            'ari_password' => 'nullable|string|max:255',

            // WebRTC Settings
            'stun_server' => 'nullable|string|max:255',
            'turn_server' => 'nullable|string|max:255',
            'turn_username' => 'nullable|string|max:255',
            'turn_credential' => 'nullable|string|max:255',

            // General Settings
            'timezone' => 'nullable|string|max:100',
            'session_timeout' => 'nullable|integer|min:5|max:1440',
            'data_retention_days' => 'nullable|integer|min:1|max:3650',
            'recording_retention_days' => 'nullable|integer|min:1|max:3650',

            // Email Settings
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_encryption' => 'nullable|in:tls,ssl,none',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
        ]);

        // Define which fields are encrypted (passwords/secrets)
        $encryptedFields = ['ami_secret', 'ari_password', 'turn_credential', 'smtp_password'];
        
        // Define field groups for organization
        $fieldGroups = [
            'ami_host' => 'ami', 'ami_port' => 'ami', 'ami_username' => 'ami', 'ami_secret' => 'ami',
            'ari_url' => 'ari', 'ari_app' => 'ari', 'ari_username' => 'ari', 'ari_password' => 'ari',
            'stun_server' => 'webrtc', 'turn_server' => 'webrtc', 'turn_username' => 'webrtc', 'turn_credential' => 'webrtc',
            'timezone' => 'general', 'session_timeout' => 'general', 'data_retention_days' => 'general', 'recording_retention_days' => 'general',
            'smtp_host' => 'email', 'smtp_port' => 'email', 'smtp_username' => 'email', 'smtp_password' => 'email',
            'smtp_encryption' => 'email', 'mail_from_address' => 'email', 'mail_from_name' => 'email',
        ];

        foreach ($validated as $key => $value) {
            // Skip empty password fields (don't overwrite with empty)
            if (in_array($key, $encryptedFields) && empty($value)) {
                continue;
            }

            $group = $fieldGroups[$key] ?? 'general';
            $isEncrypted = in_array($key, $encryptedFields);
            $type = is_int($value) ? 'integer' : 'string';

            SystemSetting::set($key, $value, $group, $type, $isEncrypted);
        }

        // Clear settings cache so new values take effect
        SettingsService::clearCache();

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

