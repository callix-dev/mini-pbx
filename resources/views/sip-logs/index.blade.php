<x-app-layout>
    @section('title', 'SIP Security Logs')
    @section('page-title', 'SIP Security Logs')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">SIP Security Logs</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Monitor all SIP traffic, rejected calls, and security events
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('sip-logs.export') }}?{{ http_build_query(request()->all()) }}" class="btn-secondary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export CSV
                </a>
                <button type="button" onclick="window.location.reload()" class="btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh
                </button>
            </div>
        </div>
    </x-slot>

    <!-- Summary Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Today -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Today</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_today']) }}</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex items-center text-sm">
                <span class="text-green-600 dark:text-green-400">{{ $stats['allowed_today'] }} allowed</span>
                <span class="mx-2 text-gray-300 dark:text-gray-600">|</span>
                <span class="text-red-600 dark:text-red-400">{{ $stats['rejected_today'] }} rejected</span>
            </div>
        </div>

        <!-- Rejected Today -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Rejected Today</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($stats['rejected_today']) }}</p>
                </div>
                <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-full">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400">
                <span>Week: {{ $stats['rejected_week'] }}</span>
                <span class="mx-2 text-gray-300 dark:text-gray-600">|</span>
                <span>Month: {{ $stats['rejected_month'] }}</span>
            </div>
        </div>

        <!-- Direction Split -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Direction (Today)</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        <span class="text-green-600">↓{{ $stats['inbound_today'] }}</span>
                        <span class="text-gray-400 mx-1">/</span>
                        <span class="text-blue-600">↑{{ $stats['outbound_today'] }}</span>
                    </p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-full">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                </div>
            </div>
            <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Inbound / Outbound
            </div>
        </div>

        <!-- Unique IPs -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Unique IPs Today</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['unique_ips_today']) }}</p>
                </div>
                <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-full">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                    </svg>
                </div>
            </div>
            <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Source addresses
            </div>
        </div>
    </div>

    <!-- Top Threats & Rejection Reasons -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Top Rejected IPs -->
        @if($topRejectedIps->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Top Rejected IPs (This Week)
            </h3>
            <div class="space-y-2">
                @foreach($topRejectedIps as $ip)
                <div class="flex items-center justify-between py-2 px-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <a href="{{ route('sip-logs.index', ['source_ip' => $ip->source_ip]) }}" 
                       class="font-mono text-sm text-gray-900 dark:text-white hover:text-red-600 dark:hover:text-red-400">
                        {{ $ip->source_ip }}
                    </a>
                    <span class="px-2 py-1 text-xs font-bold text-red-700 bg-red-200 dark:bg-red-800 dark:text-red-200 rounded-full">
                        {{ $ip->count }} rejected
                    </span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Top Rejection Reasons -->
        @if($topRejectReasons->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Top Rejection Reasons (This Week)
            </h3>
            <div class="space-y-2">
                @foreach($topRejectReasons as $reason)
                <div class="flex items-center justify-between py-2 px-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                    <span class="text-sm text-gray-900 dark:text-white truncate max-w-[200px]" title="{{ $reason->reject_reason }}">
                        {{ $reason->reject_reason }}
                    </span>
                    <span class="px-2 py-1 text-xs font-bold text-yellow-700 bg-yellow-200 dark:bg-yellow-800 dark:text-yellow-200 rounded-full">
                        {{ $reason->count }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
        <div class="p-4">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-7 gap-4">
                <div>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Search caller/callee..."
                           class="form-input">
                </div>
                <div>
                    <input type="text" name="source_ip" value="{{ request('source_ip') }}" 
                           placeholder="Source IP..."
                           class="form-input">
                </div>
                <div>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                {{ $status }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="direction" class="form-select">
                        <option value="">All Direction</option>
                        <option value="inbound" {{ request('direction') === 'inbound' ? 'selected' : '' }}>Inbound</option>
                        <option value="outbound" {{ request('direction') === 'outbound' ? 'selected' : '' }}>Outbound</option>
                    </select>
                </div>
                <div>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" 
                           class="form-input" placeholder="From date">
                </div>
                <div>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" 
                           class="form-input" placeholder="To date">
                </div>
                <div class="flex items-center space-x-2">
                    <button type="submit" class="btn-secondary flex-1">Filter</button>
                    @if(request()->hasAny(['search', 'source_ip', 'status', 'direction', 'event_type', 'date_from', 'date_to']))
                        <a href="{{ route('sip-logs.index') }}" class="btn-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Direction</th>
                        <th>Status</th>
                        <th>Source</th>
                        <th>Caller</th>
                        <th>Called</th>
                        <th>Endpoint</th>
                        <th>Reject Reason</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr class="{{ $log->status === 'REJECTED' ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                            <td>
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $log->event_time->format('M d, H:i:s') }}
                                </span>
                            </td>
                            <td>
                                @if($log->direction === 'inbound')
                                    <span class="flex items-center text-green-600 dark:text-green-400">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                        </svg>
                                        IN
                                    </span>
                                @else
                                    <span class="flex items-center text-blue-600 dark:text-blue-400">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                        </svg>
                                        OUT
                                    </span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'ALLOWED' => 'badge-success',
                                        'REJECTED' => 'badge-danger',
                                        'FAILED' => 'badge-warning',
                                        'UNKNOWN' => 'badge-gray',
                                    ];
                                @endphp
                                <span class="badge {{ $statusColors[$log->status] ?? 'badge-gray' }}">
                                    {{ $log->status }}
                                </span>
                            </td>
                            <td>
                                <div>
                                    <a href="{{ route('sip-logs.index', ['source_ip' => $log->source_ip]) }}" 
                                       class="font-mono text-sm text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400">
                                        {{ $log->source_ip }}
                                    </a>
                                    @if($log->source_port)
                                        <span class="text-xs text-gray-400">:{{ $log->source_port }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="font-mono text-sm text-gray-900 dark:text-white">
                                    {{ $log->caller_id ?: '-' }}
                                </span>
                            </td>
                            <td>
                                <span class="font-mono text-sm text-gray-900 dark:text-white truncate max-w-[150px] inline-block" title="{{ $log->callee_id }}">
                                    {{ Str::limit($log->callee_id ?: '-', 20) }}
                                </span>
                            </td>
                            <td>
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $log->endpoint ?: '-' }}
                                </span>
                            </td>
                            <td>
                                @if($log->reject_reason)
                                    <span class="text-sm text-red-600 dark:text-red-400 truncate max-w-[200px] inline-block" title="{{ $log->reject_reason }}">
                                        {{ Str::limit($log->reject_reason, 30) }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('sip-logs.show', $log) }}" 
                                   class="inline-flex items-center px-2 py-1 text-xs font-medium text-primary-700 bg-primary-100 rounded hover:bg-primary-200 dark:text-primary-400 dark:bg-primary-900/30 dark:hover:bg-primary-900/50 transition-colors"
                                   title="View Details">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No SIP logs found</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">SIP events will appear here once traffic is detected.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $logs->withQueryString()->links() }}
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        // Auto-refresh every 30 seconds if on the first page with no filters
        @if(!request()->hasAny(['search', 'source_ip', 'status', 'direction', 'date_from', 'date_to']) && $logs->currentPage() === 1)
        setTimeout(function() {
            window.location.reload();
        }, 30000);
        @endif
    </script>
    @endpush
</x-app-layout>

