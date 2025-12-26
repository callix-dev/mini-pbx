<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallLog;
use App\Models\Extension;
use App\Models\Queue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function stats(): JsonResponse
    {
        // Sync extension status from Asterisk
        $this->syncExtensionStatus();

        // Get active calls from cache (set by AMI listener)
        $cachedActiveCalls = Cache::get('active_calls', []);
        
        // Filter out stale calls and internal AppDial channels
        $activeCalls = collect($cachedActiveCalls)->filter(function ($call) {
            $cachedAt = Carbon::parse($call['cached_at'] ?? now());
            $destination = $call['destination'] ?? '';
            
            // Skip if stale (older than 5 minutes)
            if ($cachedAt->diffInMinutes(now()) >= 5) {
                return false;
            }
            
            // Skip internal AppDial channels (destination = 's', 'h', or empty)
            if (empty($destination) || $destination === 's' || $destination === 'h') {
                return false;
            }
            
            return true;
        });

        // Get statistics
        $stats = [
            'active_calls' => $activeCalls->count(),
            'agents_online' => User::where('agent_status', '!=', 'offline')->count(),
            'agents_available' => User::where('agent_status', 'available')->count(),
            'extensions_online' => Extension::where('status', 'online')->count(),
            'extensions_on_call' => Extension::where('status', 'on_call')->count(),
            'todays_calls' => CallLog::today()->count(),
            'todays_answered' => CallLog::today()->answered()->count(),
        ];

        // Format active calls for display
        $formattedActiveCalls = $activeCalls->map(function ($call) {
            $startedAt = isset($call['started_at']) ? Carbon::parse($call['started_at']) : now();
            $callerExt = Extension::where('extension', $call['caller_id'] ?? '')->first();
            
            return [
                'uniqueid' => $call['unique_id'] ?? '',
                'caller_id' => $call['caller_id'] ?? '',
                'caller_name' => $callerExt?->name ?? $call['caller_id'] ?? '',
                'destination' => $call['destination'] ?? '',
                'type' => $call['type'] ?? 'internal',
                'status' => 'active',
                'duration' => $startedAt->diffInSeconds(now()),
                'formatted_duration' => gmdate('i:s', $startedAt->diffInSeconds(now())),
                'extension' => $callerExt ? [
                    'extension' => $callerExt->extension,
                    'name' => $callerExt->name,
                ] : null,
            ];
        })->values();

        // Get recent calls
        $recentCalls = CallLog::whereNotNull('end_time')
            ->orderBy('start_time', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($call) => [
                'id' => $call->id,
                'caller_id' => $call->caller_id,
                'callee_id' => $call->callee_id,
                'caller_name' => $call->caller_name,
                'destination' => $call->callee_id,
                'type' => $call->type,
                'status' => $call->status,
                'duration' => $call->duration,
                'formatted_duration' => $call->formatted_duration,
                'time_ago' => $call->start_time?->diffForHumans(),
            ]);

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'active_calls' => $formattedActiveCalls,
            'recent_calls' => $recentCalls,
        ]);
    }

    /**
     * Sync extension registration status from Asterisk realtime tables
     */
    protected function syncExtensionStatus(): void
    {
        try {
            $contacts = DB::table('ps_contacts')
                ->pluck('via_addr', 'endpoint')
                ->toArray();

            // Get active calls from cache
            $activeCalls = Cache::get('active_calls', []);
            $extensionsInCall = [];
            
            foreach ($activeCalls as $call) {
                if (isset($call['caller_id'])) {
                    $extensionsInCall[] = $call['caller_id'];
                }
                if (isset($call['destination'])) {
                    $extensionsInCall[] = $call['destination'];
                }
            }

            Extension::chunk(100, function ($extensions) use ($contacts, $extensionsInCall) {
                foreach ($extensions as $extension) {
                    $isRegistered = isset($contacts[$extension->extension]);
                    $isInActiveCall = in_array($extension->extension, $extensionsInCall);
                    
                    // Determine the correct status
                    if ($isInActiveCall) {
                        $newStatus = 'on_call';
                    } elseif ($isRegistered) {
                        $newStatus = 'online';
                    } else {
                        $newStatus = 'offline';
                    }

                    // If extension shows on_call but isn't in any active call, reset it
                    if ($extension->status === 'on_call' && !$isInActiveCall) {
                        $newStatus = $isRegistered ? 'online' : 'offline';
                    }
                    
                    // If extension shows ringing but isn't in any active call, reset it
                    if ($extension->status === 'ringing' && !$isInActiveCall) {
                        $newStatus = $isRegistered ? 'online' : 'offline';
                    }

                    if ($extension->status !== $newStatus) {
                        $extension->status = $newStatus;
                        if ($isRegistered) {
                            $extension->last_registered_at = now();
                            $extension->last_registered_ip = $contacts[$extension->extension] ?? null;
                        }
                        $extension->saveQuietly();
                    }
                }
            });
        } catch (\Exception $e) {
            \Log::warning('Failed to sync extension status: ' . $e->getMessage());
        }
    }

    /**
     * Force reset all stale extension statuses
     */
    public function resetStaleStatuses(): JsonResponse
    {
        $activeCalls = Cache::get('active_calls', []);
        $extensionsInCall = [];
        
        foreach ($activeCalls as $call) {
            if (isset($call['caller_id'])) {
                $extensionsInCall[] = $call['caller_id'];
            }
            if (isset($call['destination'])) {
                $extensionsInCall[] = $call['destination'];
            }
        }

        // Get all extensions that show on_call or ringing
        $staleExtensions = Extension::whereIn('status', ['on_call', 'ringing'])->get();
        $resetCount = 0;

        foreach ($staleExtensions as $ext) {
            if (!in_array($ext->extension, $extensionsInCall)) {
                // Check if registered
                $isRegistered = DB::table('ps_contacts')
                    ->where('endpoint', $ext->extension)
                    ->exists();
                
                $ext->status = $isRegistered ? 'online' : 'offline';
                $ext->saveQuietly();
                $resetCount++;
            }
        }

        // Also clear stale calls from cache (older than 2 hours)
        $cleanedCalls = collect($activeCalls)->filter(function ($call) {
            $cachedAt = Carbon::parse($call['cached_at'] ?? now());
            return $cachedAt->diffInMinutes(now()) < 120;
        })->toArray();
        
        Cache::put('active_calls', $cleanedCalls, now()->addHours(2));

        return response()->json([
            'success' => true,
            'reset_count' => $resetCount,
            'message' => "Reset {$resetCount} stale extension statuses",
        ]);
    }
}

