<x-app-layout>
    @section('title', 'System Health')
    @section('page-title', 'System Health')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">System Health</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Monitor system status and performance
                </p>
            </div>
            <button onclick="location.reload()" class="btn-secondary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh
            </button>
        </div>
    </x-slot>

    <!-- Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Asterisk Status -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Asterisk</p>
                    <p class="text-lg font-semibold {{ ($health['asterisk']['connected'] ?? false) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ ($health['asterisk']['connected'] ?? false) ? 'Connected' : 'Disconnected' }}
                    </p>
                </div>
                <div class="w-12 h-12 {{ ($health['asterisk']['connected'] ?? false) ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30' }} rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 {{ ($health['asterisk']['connected'] ?? false) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if($health['asterisk']['connected'] ?? false)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        @endif
                    </svg>
                </div>
            </div>
        </div>

        <!-- Database Status -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Database</p>
                    <p class="text-lg font-semibold {{ ($health['database']['connected'] ?? false) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ ($health['database']['connected'] ?? false) ? 'Connected' : 'Disconnected' }}
                    </p>
                </div>
                <div class="w-12 h-12 {{ ($health['database']['connected'] ?? false) ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30' }} rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 {{ ($health['database']['connected'] ?? false) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Redis Status -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Redis</p>
                    <p class="text-lg font-semibold {{ ($health['redis']['connected'] ?? false) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ ($health['redis']['connected'] ?? false) ? 'Connected' : 'Disconnected' }}
                    </p>
                </div>
                <div class="w-12 h-12 {{ ($health['redis']['connected'] ?? false) ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30' }} rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 {{ ($health['redis']['connected'] ?? false) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Queue Worker Status -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Queue Worker</p>
                    <p class="text-lg font-semibold {{ ($health['queue']['running'] ?? false) ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}">
                        {{ ($health['queue']['running'] ?? false) ? 'Running' : 'Stopped' }}
                    </p>
                </div>
                <div class="w-12 h-12 {{ ($health['queue']['running'] ?? false) ? 'bg-green-100 dark:bg-green-900/30' : 'bg-yellow-100 dark:bg-yellow-900/30' }} rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 {{ ($health['queue']['running'] ?? false) ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- System Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Server Resources -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Server Resources</h3>
            </div>
            <div class="p-6 space-y-4">
                <!-- CPU Usage -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">CPU Usage</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $metrics['cpu'] ?? 0 }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        @php
                            $cpuColor = ($metrics['cpu'] ?? 0) < 70 ? 'bg-green-600' : (($metrics['cpu'] ?? 0) < 90 ? 'bg-yellow-600' : 'bg-red-600');
                        @endphp
                        <div class="{{ $cpuColor }} h-2 rounded-full" style="width: {{ min($metrics['cpu'] ?? 0, 100) }}%"></div>
                    </div>
                </div>

                <!-- Memory Usage -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Memory Usage</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $metrics['memory_used'] ?? 0 }}MB / {{ $metrics['memory_total'] ?? 0 }}MB</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        @php
                            $memPercent = ($metrics['memory_total'] ?? 1) > 0 ? (($metrics['memory_used'] ?? 0) / ($metrics['memory_total'] ?? 1) * 100) : 0;
                            $memColor = $memPercent < 70 ? 'bg-green-600' : ($memPercent < 90 ? 'bg-yellow-600' : 'bg-red-600');
                        @endphp
                        <div class="{{ $memColor }} h-2 rounded-full" style="width: {{ min($memPercent, 100) }}%"></div>
                    </div>
                </div>

                <!-- Disk Usage -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Disk Usage</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $metrics['disk_used'] ?? 0 }}GB / {{ $metrics['disk_total'] ?? 0 }}GB</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        @php
                            $diskPercent = ($metrics['disk_total'] ?? 1) > 0 ? (($metrics['disk_used'] ?? 0) / ($metrics['disk_total'] ?? 1) * 100) : 0;
                            $diskColor = $diskPercent < 70 ? 'bg-green-600' : ($diskPercent < 90 ? 'bg-yellow-600' : 'bg-red-600');
                        @endphp
                        <div class="{{ $diskColor }} h-2 rounded-full" style="width: {{ min($diskPercent, 100) }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Asterisk Stats -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Asterisk Statistics</h3>
            </div>
            <div class="p-6">
                <dl class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Calls</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $asterisk['active_calls'] ?? 0 }}</dd>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Channels</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $asterisk['active_channels'] ?? 0 }}</dd>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Registered Extensions</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $asterisk['registered_extensions'] ?? 0 }}</dd>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Uptime</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $asterisk['uptime'] ?? 'N/A' }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <!-- Recent Errors -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Errors</h3>
            <a href="{{ route('audit-logs.index') }}?type=error" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">
                View all logs
            </a>
        </div>
        <div class="p-6">
            @if(isset($errors) && count($errors) > 0)
                <div class="space-y-4">
                    @foreach($errors as $error)
                        <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <div class="ml-3 flex-1">
                                    <p class="text-sm font-medium text-red-800 dark:text-red-300">{{ $error['message'] }}</p>
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $error['time'] ?? '' }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-center text-gray-500 dark:text-gray-400 py-8">No recent errors. System is running smoothly!</p>
            @endif
        </div>
    </div>
</x-app-layout>


