<x-app-layout>
    @section('title', 'Call Analytics')
    @section('page-title', 'Call Analytics')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Call Analytics</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Detailed analytics and reporting for your call center
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('call-logs.export') }}?{{ http_build_query(request()->all()) }}" class="btn-secondary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export Report
                </a>
            </div>
        </div>
    </x-slot>

    <!-- Date Range Filter -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
        <div class="p-4">
            <form method="GET" class="flex flex-wrap items-center gap-4">
                <div>
                    <select name="period" class="form-select" onchange="this.form.submit()">
                        <option value="today" {{ request('period', 'today') === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="yesterday" {{ request('period') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                        <option value="week" {{ request('period') === 'week' ? 'selected' : '' }}>This Week</option>
                        <option value="month" {{ request('period') === 'month' ? 'selected' : '' }}>This Month</option>
                        <option value="custom" {{ request('period') === 'custom' ? 'selected' : '' }}>Custom Range</option>
                    </select>
                </div>
                <div>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input">
                </div>
                <div>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input">
                </div>
                <button type="submit" class="btn-secondary">Apply</button>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Calls</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['total_calls'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Answer Rate</p>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1">{{ number_format($stats['answer_rate'] ?? 0, 1) }}%</p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Avg Duration</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['avg_duration'] ?? '0:00' }}</p>
                </div>
                <div class="w-12 h-12 bg-accent-100 dark:bg-accent-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-accent-600 dark:text-accent-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Avg Wait Time</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['avg_wait'] ?? '0:00' }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Calls by Hour -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Calls by Hour</h3>
            </div>
            <div class="p-6">
                <div class="h-64 flex items-end justify-between space-x-2">
                    @foreach($hourlyData ?? array_fill(0, 24, 0) as $hour => $count)
                        @php
                            $maxCount = max($hourlyData ?? [1]);
                            $height = $maxCount > 0 ? ($count / $maxCount * 100) : 0;
                        @endphp
                        <div class="flex-1 flex flex-col items-center">
                            <div class="w-full bg-primary-600 rounded-t" style="height: {{ $height }}%"></div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $hour }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Call Types Distribution -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Call Distribution</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Inbound</span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($stats['inbound_calls'] ?? 0) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                            <div class="bg-green-600 h-3 rounded-full" style="width: {{ ($stats['total_calls'] ?? 0) > 0 ? (($stats['inbound_calls'] ?? 0) / ($stats['total_calls'] ?? 1) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Outbound</span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($stats['outbound_calls'] ?? 0) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                            <div class="bg-blue-600 h-3 rounded-full" style="width: {{ ($stats['total_calls'] ?? 0) > 0 ? (($stats['outbound_calls'] ?? 0) / ($stats['total_calls'] ?? 1) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Answered</span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($stats['answered_calls'] ?? 0) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                            <div class="bg-primary-600 h-3 rounded-full" style="width: {{ ($stats['total_calls'] ?? 0) > 0 ? (($stats['answered_calls'] ?? 0) / ($stats['total_calls'] ?? 1) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Missed</span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($stats['missed_calls'] ?? 0) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                            <div class="bg-red-600 h-3 rounded-full" style="width: {{ ($stats['total_calls'] ?? 0) > 0 ? (($stats['missed_calls'] ?? 0) / ($stats['total_calls'] ?? 1) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Agent Performance -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Agent Performance</h3>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th>Total Calls</th>
                        <th>Answered</th>
                        <th>Missed</th>
                        <th>Avg Duration</th>
                        <th>Avg Wait</th>
                        <th>Answer Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agentStats ?? [] as $agent)
                        <tr>
                            <td>
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-primary-600 rounded-full flex items-center justify-center text-white text-sm font-medium mr-3">
                                        {{ strtoupper(substr($agent['name'], 0, 2)) }}
                                    </div>
                                    <span class="text-gray-900 dark:text-white">{{ $agent['name'] }}</span>
                                </div>
                            </td>
                            <td>{{ number_format($agent['total_calls']) }}</td>
                            <td class="text-green-600 dark:text-green-400">{{ number_format($agent['answered']) }}</td>
                            <td class="text-red-600 dark:text-red-400">{{ number_format($agent['missed']) }}</td>
                            <td>{{ $agent['avg_duration'] }}</td>
                            <td>{{ $agent['avg_wait'] }}</td>
                            <td>
                                <span class="badge {{ $agent['answer_rate'] >= 80 ? 'badge-success' : ($agent['answer_rate'] >= 60 ? 'badge-warning' : 'badge-danger') }}">
                                    {{ number_format($agent['answer_rate'], 1) }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                No agent data available for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Queue Performance -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Queue Performance</h3>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Queue</th>
                        <th>Total Calls</th>
                        <th>Answered</th>
                        <th>Abandoned</th>
                        <th>Avg Wait</th>
                        <th>Avg Duration</th>
                        <th>SLA</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($queueStats ?? [] as $queue)
                        <tr>
                            <td>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $queue['name'] }}</span>
                            </td>
                            <td>{{ number_format($queue['total_calls']) }}</td>
                            <td class="text-green-600 dark:text-green-400">{{ number_format($queue['answered']) }}</td>
                            <td class="text-red-600 dark:text-red-400">{{ number_format($queue['abandoned']) }}</td>
                            <td>{{ $queue['avg_wait'] }}</td>
                            <td>{{ $queue['avg_duration'] }}</td>
                            <td>
                                <span class="badge {{ $queue['sla'] >= 80 ? 'badge-success' : ($queue['sla'] >= 60 ? 'badge-warning' : 'badge-danger') }}">
                                    {{ number_format($queue['sla'], 1) }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                No queue data available for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>


