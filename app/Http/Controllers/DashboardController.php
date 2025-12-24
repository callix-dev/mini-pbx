<?php

namespace App\Http\Controllers;

use App\Models\CallLog;
use App\Models\Extension;
use App\Models\Queue;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Get statistics for dashboard
        $stats = [
            'active_calls' => CallLog::whereNull('end_time')->count(),
            'agents_online' => User::where('agent_status', '!=', 'offline')->count(),
            'agents_available' => User::where('agent_status', 'available')->count(),
            'queue_waiting' => 0, // Will be populated from AMI
            'todays_calls' => CallLog::today()->count(),
            'todays_answered' => CallLog::today()->answered()->count(),
        ];

        // Get all extensions with their status
        $extensions = Extension::with('user')
            ->active()
            ->orderByRaw("CASE status 
                WHEN 'on_call' THEN 1 
                WHEN 'ringing' THEN 2 
                WHEN 'online' THEN 3 
                ELSE 4 
            END")
            ->get();

        // Get recent/active calls
        $activeCalls = CallLog::with(['extension', 'queue'])
            ->whereNull('end_time')
            ->orderBy('start_time', 'desc')
            ->limit(10)
            ->get();

        // Get recent completed calls
        $recentCalls = CallLog::with(['extension'])
            ->whereNotNull('end_time')
            ->orderBy('start_time', 'desc')
            ->limit(10)
            ->get();

        // Get online agents
        $onlineAgents = User::with('extension')
            ->where('agent_status', '!=', 'offline')
            ->orderBy('agent_status')
            ->limit(10)
            ->get();

        // Get queue statistics
        $queues = Queue::with(['members' => function ($query) {
            $query->where('is_logged_in', true);
        }])
            ->where('is_active', true)
            ->get();

        // Get parked calls (will be populated from AMI)
        $parkedCalls = collect();

        return view('dashboard', compact(
            'stats', 
            'extensions',
            'activeCalls', 
            'recentCalls',
            'onlineAgents', 
            'queues', 
            'parkedCalls'
        ));
    }
}

