<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallLog;
use App\Models\Extension;
use App\Models\Queue;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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

        // Get statistics
        $stats = [
            'active_calls' => CallLog::whereNull('end_time')->count(),
            'agents_online' => User::where('agent_status', '!=', 'offline')->count(),
            'agents_available' => User::where('agent_status', 'available')->count(),
            'extensions_online' => Extension::where('status', 'online')->count(),
            'extensions_on_call' => Extension::where('status', 'on_call')->count(),
            'todays_calls' => CallLog::today()->count(),
            'todays_answered' => CallLog::today()->answered()->count(),
        ];

        // Get active calls
        $activeCalls = CallLog::with(['extension'])
            ->whereNull('end_time')
            ->orderBy('start_time', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($call) => [
                'id' => $call->id,
                'uniqueid' => $call->uniqueid,
                'caller_id' => $call->caller_id,
                'destination' => $call->destination,
                'type' => $call->type,
                'status' => $call->status,
                'duration' => $call->start_time ? now()->diffInSeconds($call->start_time) : 0,
                'extension' => $call->extension ? [
                    'extension' => $call->extension->extension,
                    'name' => $call->extension->name,
                ] : null,
            ]);

        // Get recent calls
        $recentCalls = CallLog::whereNotNull('end_time')
            ->orderBy('start_time', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($call) => [
                'id' => $call->id,
                'caller_id' => $call->caller_id,
                'destination' => $call->destination,
                'type' => $call->type,
                'status' => $call->status,
                'duration' => $call->duration,
                'formatted_duration' => $call->formatted_duration,
                'time_ago' => $call->start_time?->diffForHumans(),
            ]);

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'active_calls' => $activeCalls,
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

