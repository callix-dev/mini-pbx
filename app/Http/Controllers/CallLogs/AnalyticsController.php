<?php

namespace App\Http\Controllers\CallLogs;

use App\Http\Controllers\Controller;
use App\Models\CallLog;
use App\Models\Extension;
use App\Models\Queue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        // Overall statistics
        $stats = [
            'total_calls' => CallLog::whereBetween('start_time', [$dateFrom, $dateTo . ' 23:59:59'])->count(),
            'answered_calls' => CallLog::whereBetween('start_time', [$dateFrom, $dateTo . ' 23:59:59'])->answered()->count(),
            'missed_calls' => CallLog::whereBetween('start_time', [$dateFrom, $dateTo . ' 23:59:59'])->missed()->count(),
            'inbound_calls' => CallLog::whereBetween('start_time', [$dateFrom, $dateTo . ' 23:59:59'])->inbound()->count(),
            'outbound_calls' => CallLog::whereBetween('start_time', [$dateFrom, $dateTo . ' 23:59:59'])->outbound()->count(),
            'avg_duration' => CallLog::whereBetween('start_time', [$dateFrom, $dateTo . ' 23:59:59'])->answered()->avg('billable_duration') ?? 0,
            'avg_wait_time' => CallLog::whereBetween('start_time', [$dateFrom, $dateTo . ' 23:59:59'])->avg('wait_time') ?? 0,
        ];

        $stats['answer_rate'] = $stats['total_calls'] > 0 
            ? round(($stats['answered_calls'] / $stats['total_calls']) * 100, 1) 
            : 0;

        // Calls by hour
        $callsByHour = CallLog::whereBetween('start_time', [$dateFrom, $dateTo . ' 23:59:59'])
            ->select(DB::raw('EXTRACT(HOUR FROM start_time) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        // Calls by day
        $callsByDay = CallLog::whereBetween('start_time', [$dateFrom, $dateTo . ' 23:59:59'])
            ->select(DB::raw('DATE(start_time) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Calls by status
        $callsByStatus = CallLog::whereBetween('start_time', [$dateFrom, $dateTo . ' 23:59:59'])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Agent performance
        $agentPerformance = CallLog::whereBetween('start_time', [$dateFrom, $dateTo . ' 23:59:59'])
            ->whereNotNull('extension_id')
            ->select(
                'extension_id',
                DB::raw('COUNT(*) as total_calls'),
                DB::raw('SUM(CASE WHEN status = \'answered\' THEN 1 ELSE 0 END) as answered_calls'),
                DB::raw('AVG(billable_duration) as avg_duration'),
                DB::raw('SUM(billable_duration) as total_duration')
            )
            ->groupBy('extension_id')
            ->with('extension')
            ->orderByDesc('total_calls')
            ->limit(20)
            ->get();

        // Queue performance
        $queuePerformance = CallLog::whereBetween('start_time', [$dateFrom, $dateTo . ' 23:59:59'])
            ->whereNotNull('queue_id')
            ->select(
                'queue_id',
                DB::raw('COUNT(*) as total_calls'),
                DB::raw('SUM(CASE WHEN status = \'answered\' THEN 1 ELSE 0 END) as answered_calls'),
                DB::raw('AVG(wait_time) as avg_wait_time'),
                DB::raw('MAX(wait_time) as max_wait_time')
            )
            ->groupBy('queue_id')
            ->with('queue')
            ->orderByDesc('total_calls')
            ->get();

        return view('call-logs.analytics', compact(
            'stats', 'callsByHour', 'callsByDay', 'callsByStatus',
            'agentPerformance', 'queuePerformance', 'dateFrom', 'dateTo'
        ));
    }
}







