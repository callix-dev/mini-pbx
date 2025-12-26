<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::with('user');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('auditable_type', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%");
            });
        }

        $logs = $query->latest('created_at')->paginate(50);
        $actions = AuditLog::ACTIONS;

        return view('platform.audit-logs.index', compact('logs', 'actions'));
    }

    public function show(AuditLog $auditLog): View
    {
        return view('platform.audit-logs.show', compact('auditLog'));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = AuditLog::with('user');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->latest('created_at')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="audit_logs_' . date('Y-m-d_His') . '.csv"',
        ];

        return response()->stream(function () use ($logs) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Date/Time', 'User', 'Action', 'Model', 'Model ID',
                'Description', 'IP Address'
            ]);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user_name,
                    $log->action,
                    class_basename($log->auditable_type),
                    $log->auditable_id,
                    $log->description,
                    $log->ip_address,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}


