<?php

namespace App\Http\Controllers\Api;

use App\Console\Commands\AmiListener;
use App\Http\Controllers\Controller;
use App\Models\ExtensionGroup;
use App\Services\Asterisk\AsteriskQueueSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WaitingCallsController extends Controller
{
    /**
     * Get waiting calls for the current user
     * 
     * Agents see only their own groups' calls
     * Admin/QA/Manager see all waiting calls
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $allWaitingCalls = Cache::get('waiting_calls', []);
        
        // Check if user has elevated access
        $hasElevatedAccess = $user->hasAnyRole(['admin', 'qa', 'manager', 'super-admin']);
        
        $result = [];
        
        if ($hasElevatedAccess) {
            // Show all waiting calls
            foreach ($allWaitingCalls as $queueName => $calls) {
                $groupId = AsteriskQueueSyncService::getGroupIdFromQueueName($queueName);
                $group = $groupId ? ExtensionGroup::find($groupId) : null;
                
                foreach ($calls as $channel => $call) {
                    $result[] = $this->formatWaitingCall($call, $group, $queueName);
                }
            }
        } else {
            // Show only calls from groups the user's extension belongs to
            $userExtension = $user->extension;
            
            if ($userExtension) {
                $userGroupIds = $userExtension->groups()->pluck('extension_groups.id')->toArray();
                
                foreach ($allWaitingCalls as $queueName => $calls) {
                    $groupId = AsteriskQueueSyncService::getGroupIdFromQueueName($queueName);
                    
                    if ($groupId && in_array($groupId, $userGroupIds)) {
                        $group = ExtensionGroup::find($groupId);
                        
                        foreach ($calls as $channel => $call) {
                            $result[] = $this->formatWaitingCall($call, $group, $queueName);
                        }
                    }
                }
            }
        }
        
        // Sort by wait time (longest first)
        usort($result, function ($a, $b) {
            return $b['wait_seconds'] <=> $a['wait_seconds'];
        });
        
        return response()->json([
            'success' => true,
            'waiting_calls' => $result,
            'total_count' => count($result),
            'has_elevated_access' => $hasElevatedAccess,
        ]);
    }

    /**
     * Format a waiting call for the API response
     */
    private function formatWaitingCall(array $call, ?ExtensionGroup $group, string $queueName): array
    {
        $joinedAt = isset($call['joined_at']) ? \Carbon\Carbon::parse($call['joined_at']) : now();
        $waitSeconds = $joinedAt->diffInSeconds(now());
        
        return [
            'channel' => $call['channel'],
            'unique_id' => $call['unique_id'] ?? null,
            'caller_id' => $call['caller_id'] ?? 'Unknown',
            'caller_name' => $call['caller_name'] ?? null,
            'queue_name' => $queueName,
            'group_id' => $group?->id,
            'group_name' => $group?->name ?? $queueName,
            'position' => $call['position'] ?? 1,
            'joined_at' => $call['joined_at'] ?? now()->toIso8601String(),
            'wait_seconds' => $waitSeconds,
            'wait_formatted' => $this->formatDuration($waitSeconds),
            'did' => $call['did'] ?? null,
        ];
    }

    /**
     * Format duration in seconds to MM:SS
     */
    private function formatDuration(int $seconds): string
    {
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return sprintf('%02d:%02d', $minutes, $secs);
    }

    /**
     * Pickup a waiting call
     * 
     * Redirects the waiting caller to the agent's extension
     */
    public function pickup(Request $request, string $channel): JsonResponse
    {
        $user = $request->user();
        $extension = $user->extension;
        
        if (!$extension) {
            return response()->json([
                'success' => false,
                'message' => 'No extension assigned to your account',
            ], 400);
        }
        
        // Verify the call exists in waiting calls
        $waitingCalls = Cache::get('waiting_calls', []);
        $callFound = false;
        $queueName = null;
        
        foreach ($waitingCalls as $queue => $calls) {
            if (isset($calls[$channel])) {
                $callFound = true;
                $queueName = $queue;
                break;
            }
        }
        
        if (!$callFound) {
            return response()->json([
                'success' => false,
                'message' => 'Call not found or already answered',
            ], 404);
        }
        
        // Check if user has permission to pickup this call
        $hasElevatedAccess = $user->hasAnyRole(['admin', 'qa', 'manager', 'super-admin']);
        
        if (!$hasElevatedAccess) {
            // Check if user's extension is in the same group
            $groupId = AsteriskQueueSyncService::getGroupIdFromQueueName($queueName);
            $userGroupIds = $extension->groups()->pluck('extension_groups.id')->toArray();
            
            if (!in_array($groupId, $userGroupIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to pickup this call',
                ], 403);
            }
        }
        
        // Send AMI command to redirect the call
        try {
            $this->sendAmiRedirect($channel, 'dial-extension', $extension->extension);
            
            Log::info('Call pickup initiated', [
                'channel' => $channel,
                'agent_extension' => $extension->extension,
                'user_id' => $user->id,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Call pickup initiated',
            ]);
        } catch (\Exception $e) {
            Log::error('Call pickup failed', [
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to pickup call: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Redirect a waiting call to another extension group
     * 
     * Only available to Admin/QA/Manager
     */
    public function redirect(Request $request, string $channel): JsonResponse
    {
        $request->validate([
            'group_id' => 'required|exists:extension_groups,id',
        ]);
        
        $user = $request->user();
        
        // Only elevated users can redirect
        if (!$user->hasAnyRole(['admin', 'qa', 'manager', 'super-admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to redirect calls',
            ], 403);
        }
        
        // Verify the call exists
        $waitingCalls = Cache::get('waiting_calls', []);
        $callFound = false;
        
        foreach ($waitingCalls as $queue => $calls) {
            if (isset($calls[$channel])) {
                $callFound = true;
                break;
            }
        }
        
        if (!$callFound) {
            return response()->json([
                'success' => false,
                'message' => 'Call not found or already answered',
            ], 404);
        }
        
        $targetGroupId = $request->input('group_id');
        $targetGroup = ExtensionGroup::find($targetGroupId);
        
        if (!$targetGroup || !$targetGroup->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Target group not found or inactive',
            ], 404);
        }
        
        // Send AMI command to redirect to the new group's queue
        try {
            $this->sendAmiRedirect($channel, 'extgroup-handler', (string) $targetGroupId);
            
            Log::info('Call redirect initiated', [
                'channel' => $channel,
                'target_group_id' => $targetGroupId,
                'target_group_name' => $targetGroup->name,
                'user_id' => $user->id,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Call redirected to ' . $targetGroup->name,
            ]);
        } catch (\Exception $e) {
            Log::error('Call redirect failed', [
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to redirect call: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all extension groups (for redirect dropdown)
     */
    public function groups(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Only elevated users can see all groups for redirect
        if (!$user->hasAnyRole(['admin', 'qa', 'manager', 'super-admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Not authorized',
            ], 403);
        }
        
        $groups = ExtensionGroup::active()
            ->withCount('extensions')
            ->orderBy('name')
            ->get(['id', 'name', 'ring_strategy']);
        
        return response()->json([
            'success' => true,
            'groups' => $groups,
        ]);
    }

    /**
     * Send AMI Redirect command
     */
    private function sendAmiRedirect(string $channel, string $context, string $exten, int $priority = 1): void
    {
        $settings = \App\Services\SettingsService::getAmiSettings();
        
        $socket = fsockopen($settings['host'], $settings['port'], $errno, $errstr, 5);
        
        if (!$socket) {
            throw new \RuntimeException("Failed to connect to AMI: $errstr ($errno)");
        }
        
        try {
            // Read welcome
            fgets($socket);
            
            // Login
            $login = "Action: Login\r\n";
            $login .= "Username: {$settings['username']}\r\n";
            $login .= "Secret: {$settings['password']}\r\n";
            $login .= "\r\n";
            fwrite($socket, $login);
            
            // Read login response
            $this->readAmiResponse($socket);
            
            // Send redirect
            $redirect = "Action: Redirect\r\n";
            $redirect .= "Channel: {$channel}\r\n";
            $redirect .= "Context: {$context}\r\n";
            $redirect .= "Exten: {$exten}\r\n";
            $redirect .= "Priority: {$priority}\r\n";
            $redirect .= "\r\n";
            fwrite($socket, $redirect);
            
            // Read response
            $response = $this->readAmiResponse($socket);
            
            if (($response['Response'] ?? '') !== 'Success') {
                throw new \RuntimeException($response['Message'] ?? 'Redirect failed');
            }
            
            // Logoff
            fwrite($socket, "Action: Logoff\r\n\r\n");
        } finally {
            fclose($socket);
        }
    }

    /**
     * Read AMI response
     */
    private function readAmiResponse($socket): array
    {
        $response = [];
        stream_set_timeout($socket, 5);
        
        while (($line = fgets($socket)) !== false) {
            $line = trim($line);
            
            if ($line === '') {
                if (!empty($response)) {
                    return $response;
                }
                continue;
            }
            
            if (strpos($line, ': ') !== false) {
                [$key, $value] = explode(': ', $line, 2);
                $response[$key] = $value;
            }
        }
        
        return $response;
    }
}



