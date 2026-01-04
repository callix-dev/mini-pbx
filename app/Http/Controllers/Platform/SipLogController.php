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
        // Get summary statistics
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        $stats = [
            'total_today' => SipSecurityLog::whereDate('event_time', $today)->count(),
            'rejected_today' => SipSecurityLog::whereDate('event_time', $today)->rejected()->count(),
            'allowed_today' => SipSecurityLog::whereDate('event_time', $today)->allowed()->count(),
            'inbound_today' => SipSecurityLog::whereDate('event_time', $today)->inbound()->count(),
            'outbound_today' => SipSecurityLog::whereDate('event_time', $today)->outbound()->count(),
            'unique_ips_today' => SipSecurityLog::whereDate('event_time', $today)->distinct('source_ip')->count('source_ip'),
            'rejected_week' => SipSecurityLog::where('event_time', '>=', $thisWeek)->rejected()->count(),
            'rejected_month' => SipSecurityLog::where('event_time', '>=', $thisMonth)->rejected()->count(),
        ];

        // Get top rejected IPs (potential threats)
        $topRejectedIps = SipSecurityLog::selectRaw('source_ip, COUNT(*) as count')
            ->rejected()
            ->where('event_time', '>=', $thisWeek)
            ->groupBy('source_ip')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Get top rejection reasons
        $topRejectReasons = SipSecurityLog::selectRaw('reject_reason, COUNT(*) as count')
            ->rejected()
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
        return view('sip-logs.show', compact('sipLog'));
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

