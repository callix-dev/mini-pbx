<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\SipSecurityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class SipLogController extends Controller
{
    public function index(Request $request): View
    {
        // Get summary statistics with optimized single query
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        // Single optimized query for today's stats
        $todayStats = SipSecurityLog::whereDate('event_time', $today)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'REJECTED' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = 'ALLOWED' THEN 1 ELSE 0 END) as allowed,
                SUM(CASE WHEN direction = 'inbound' THEN 1 ELSE 0 END) as inbound,
                SUM(CASE WHEN direction = 'outbound' THEN 1 ELSE 0 END) as outbound,
                COUNT(DISTINCT source_ip) as unique_ips
            ")
            ->first();

        // Rejected counts for week and month (separate queries for index usage)
        $rejectedWeek = SipSecurityLog::where('event_time', '>=', $thisWeek)
            ->where('status', 'REJECTED')
            ->count();
        
        $rejectedMonth = SipSecurityLog::where('event_time', '>=', $thisMonth)
            ->where('status', 'REJECTED')
            ->count();

        $stats = [
            'total_today' => $todayStats->total ?? 0,
            'rejected_today' => $todayStats->rejected ?? 0,
            'allowed_today' => $todayStats->allowed ?? 0,
            'inbound_today' => $todayStats->inbound ?? 0,
            'outbound_today' => $todayStats->outbound ?? 0,
            'unique_ips_today' => $todayStats->unique_ips ?? 0,
            'rejected_week' => $rejectedWeek,
            'rejected_month' => $rejectedMonth,
        ];

        // Get top rejected IPs (potential threats) - with index hint
        $topRejectedIps = SipSecurityLog::selectRaw('source_ip, COUNT(*) as count')
            ->where('status', 'REJECTED')
            ->where('event_time', '>=', $thisWeek)
            ->groupBy('source_ip')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Get top rejection reasons
        $topRejectReasons = SipSecurityLog::selectRaw('reject_reason, COUNT(*) as count')
            ->where('status', 'REJECTED')
            ->whereNotNull('reject_reason')
            ->where('event_time', '>=', $thisWeek)
            ->groupBy('reject_reason')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // Build query with filters
        $query = SipSecurityLog::query()->latest('event_time');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by direction
        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }

        // Filter by event type
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        // Filter by source IP
        if ($request->filled('source_ip')) {
            $query->where('source_ip', 'like', '%' . $request->source_ip . '%');
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('event_time', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('event_time', '<=', $request->date_to);
        }

        // Search by caller/callee
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('caller_id', 'like', "%{$search}%")
                    ->orWhere('callee_id', 'like', "%{$search}%")
                    ->orWhere('from_uri', 'like', "%{$search}%")
                    ->orWhere('to_uri', 'like', "%{$search}%")
                    ->orWhere('call_id', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(50);

        // Get unique statuses and event types for filter dropdowns
        $statuses = [
            SipSecurityLog::STATUS_ALLOWED,
            SipSecurityLog::STATUS_REJECTED,
            SipSecurityLog::STATUS_FAILED,
        ];

        $eventTypes = [
            SipSecurityLog::EVENT_INVITE,
            SipSecurityLog::EVENT_REGISTER,
            SipSecurityLog::EVENT_OPTIONS,
            SipSecurityLog::EVENT_BYE,
        ];

        return view('sip-logs.index', compact(
            'logs',
            'stats',
            'topRejectedIps',
            'topRejectReasons',
            'statuses',
            'eventTypes'
        ));
    }

    public function show(int $sipLog): View
    {
        $sipLog = SipSecurityLog::findOrFail($sipLog);
        
        // Get related logs from same IP (limit to last 5)
        $relatedLogs = SipSecurityLog::where('source_ip', $sipLog->source_ip)
            ->where('id', '!=', $sipLog->id)
            ->latest('event_time')
            ->limit(5)
            ->get(['id', 'event_time', 'status', 'callee_id']);

        return view('sip-logs.show', compact('sipLog', 'relatedLogs'));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = SipSecurityLog::query()->latest('event_time');

        // Apply same filters as index
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('event_time', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('event_time', '<=', $request->date_to);
        }

        $logs = $query->limit(10000)->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sip_logs_' . date('Y-m-d_His') . '.csv"',
        ];

        return response()->stream(function () use ($logs) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, [
                'Date/Time', 'Direction', 'Event Type', 'Status',
                'Source IP', 'Source Port', 'Dest IP', 'Dest Port',
                'Caller ID', 'Called Number', 'Endpoint',
                'Reject Reason', 'SIP Code', 'Call ID'
            ]);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->event_time->format('Y-m-d H:i:s'),
                    $log->direction,
                    $log->event_type,
                    $log->status,
                    $log->source_ip,
                    $log->source_port,
                    $log->destination_ip,
                    $log->destination_port,
                    $log->caller_id,
                    $log->callee_id,
                    $log->endpoint,
                    $log->reject_reason,
                    $log->sip_response_code,
                    $log->call_id,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}

