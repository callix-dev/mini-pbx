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
        
        // Filter out stale calls (older than 5 minutes without update)
        $activeCalls = collect($cachedActiveCalls)->filter(function ($call) {
            $cachedAt = Carbon::parse($call['cached_at'] ?? now());
            return $cachedAt->diffInMinutes(now()) < 5;
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

            Extension::chunk(100, function ($extensions) use ($contacts) {
                foreach ($extensions as $extension) {
                    $isRegistered = isset($contacts[$extension->extension]);
                    $newStatus = $isRegistered ? 'online' : 'offline';

                    if (!in_array($extension->status, ['on_call', 'ringing'])) {
                        if ($extension->status !== $newStatus) {
                            $extension->status = $newStatus;
                            if ($isRegistered) {
                                $extension->last_registered_at = now();
                                $extension->last_registered_ip = $contacts[$extension->extension];
                            }
                            $extension->saveQuietly();
                        }
                    }
                }
            });
        } catch (\Exception $e) {
            \Log::warning('Failed to sync extension status: ' . $e->getMessage());
        }
    }
}

