<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebPhoneController extends Controller
{
    /**
     * Get WebPhone credentials for the authenticated user.
     */
    public function credentials(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Check if user has an extension assigned
        if (!$user->extension_id || !$user->extension) {
            return response()->json([
                'success' => false,
                'message' => 'No extension assigned to your account.',
            ], 400);
        }

        $extension = $user->extension;

        // Check if extension is active
        if (!$extension->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your extension is currently disabled.',
            ], 400);
        }

        // Get WebSocket server URL from config or env
        $wssServer = config('webphone.wss_server', env('WEBPHONE_WSS_SERVER'));
        
        if (!$wssServer) {
            // Default to same host with /ws path
            $wssServer = 'wss://' . $request->getHost() . '/ws';
        }

        // Get WebRTC settings (STUN/TURN) from SettingsService (DB -> ENV fallback)
        $webrtcSettings = SettingsService::getWebRtcSettings();

        return response()->json([
            'success' => true,
            'credentials' => [
                'extension' => $extension->extension,
                'password' => $extension->password,
                'name' => $extension->caller_id_name ?? $user->name,
                'caller_id' => $extension->caller_id_number ?? $extension->extension,
                'wss_server' => $wssServer,
                'realm' => config('webphone.realm', $request->getHost()),
            ],
            'webrtc' => [
                'stun_server' => $webrtcSettings['stun_server'],
                'turn_server' => $webrtcSettings['turn_server'],
                'turn_username' => $webrtcSettings['turn_username'],
                'turn_credential' => $webrtcSettings['turn_credential'],
            ],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'status' => $user->agent_status,
            ],
        ]);
    }

    /**
     * Update user's agent status.
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:available,not_ready,on_break,wrap_up',
        ]);

        $user = $request->user();
        $user->setStatus($request->status);

        // Broadcast status change
        broadcast(new \App\Events\AgentStatusChanged($user))->toOthers();

        return response()->json([
            'success' => true,
            'status' => $user->agent_status,
        ]);
    }

    /**
     * Log a call event from the WebPhone.
     */
    public function logEvent(Request $request): JsonResponse
    {
        $request->validate([
            'event' => 'required|string|in:registered,unregistered,call_started,call_ended,call_failed,call_missed',
            'details' => 'nullable|array',
        ]);

        $user = $request->user();

        // Log the event for debugging/monitoring
        \Log::info('WebPhone Event', [
            'user_id' => $user->id,
            'extension' => $user->extension?->extension,
            'event' => $request->event,
            'details' => $request->details,
        ]);

        // Update user status based on event
        switch ($request->event) {
            case 'registered':
                if ($user->agent_status === 'offline') {
                    $user->setStatus('available');
                }
                break;
            case 'unregistered':
                $user->setStatus('offline');
                break;
            case 'call_started':
                $user->setStatus('on_call');
                break;
            case 'call_ended':
                if ($user->agent_status === 'on_call') {
                    $user->setStatus('available');
                }
                break;
        }

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Get dial settings and restrictions.
     */
    public function dialSettings(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'success' => true,
            'settings' => [
                // Dial rules (can be expanded based on user's permissions)
                'allow_internal' => true,
                'allow_external' => $user->can('make_external_calls'),
                'allow_international' => $user->can('make_international_calls'),
                
                // Feature codes
                'feature_codes' => config('asterisk.feature_codes'),
                
                // Recording settings
                'auto_record' => config('asterisk.recordings.auto_record', false),
                
                // Transfer options
                'allow_blind_transfer' => true,
                'allow_attended_transfer' => true,
            ],
        ]);
    }
}

