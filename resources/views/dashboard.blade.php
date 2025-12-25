<x-app-layout>
    @section('title', 'Dashboard')
    @section('page-title', 'Dashboard')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Welcome back, {{ auth()->user()->name }}!
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Here's what's happening with your PBX system today.
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ now()->format('l, F j, Y') }}
                </span>
            </div>
        </div>
    </x-slot>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6" x-data="dashboardStats()">
        <!-- Active Calls -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Calls</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1" id="active-calls-count">{{ $stats['active_calls'] }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        <span class="text-primary-600 dark:text-primary-400">Live</span>
                    </p>
                </div>
                <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Extensions Online -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Extensions Online</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1" id="extensions-online-count">{{ $extensions->where('status', 'online')->count() }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        <span class="text-green-600 dark:text-green-400">{{ $extensions->where('status', 'online')->count() }} available</span> · 
                        <span class="text-red-600 dark:text-red-400">{{ $extensions->where('status', 'on_call')->count() }} on call</span>
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Queues -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Queues</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $queues->count() }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        <span class="text-accent-600 dark:text-accent-400">{{ $queues->sum(fn($q) => $q->members->count()) }} agents</span>
                    </p>
                </div>
                <div class="w-12 h-12 bg-accent-100 dark:bg-accent-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-accent-600 dark:text-accent-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Today's Calls -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Today's Calls</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['todays_calls'] }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        @if($stats['todays_calls'] > 0)
                            <span class="text-green-600 dark:text-green-400">{{ round(($stats['todays_answered'] / $stats['todays_calls']) * 100) }}%</span> answered
                        @else
                            <span class="text-gray-400">No calls yet</span>
                        @endif
                    </p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Live Calls Panel -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Live Calls</h3>
                <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-sm font-medium rounded-full" id="live-calls-badge">
                    {{ $activeCalls->count() }} Active
                </span>
            </div>
            <div class="p-6">
                <div class="space-y-4" id="active-calls-list">
                    @forelse($activeCalls as $call)
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg" id="call-{{ $call->uniqueid }}">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $call->caller_id ?: 'Unknown' }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        @if($call->extension)
                                            Agent: {{ $call->extension->name }}
                                        @endif
                                        @if($call->queue)
                                            · {{ $call->queue->name }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <span class="text-sm font-mono text-gray-600 dark:text-gray-300">
                                    {{ $call->start_time ? $call->start_time->diffForHumans(null, true) : '--:--' }}
                                </span>
                                <div class="flex space-x-2">
                                    <button class="p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg" title="Listen">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                                        </svg>
                                    </button>
                                    <button class="p-2 text-gray-400 hover:text-yellow-600 dark:hover:text-yellow-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg" title="Whisper">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                        </svg>
                                    </button>
                                    <button class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg" title="Barge">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <p>No active calls at the moment</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Extension Status Panel -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Extension Status</h3>
                <a href="{{ route('extensions.index') }}" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">View All</a>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    @forelse($extensions->take(8) as $extension)
                        <div class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors" data-extension-id="{{ $extension->id }}">
                            <div class="flex items-center space-x-3">
                                <div class="relative">
                                    <div class="w-10 h-10 bg-gradient-to-br from-primary-400 to-accent-400 rounded-full flex items-center justify-center text-white font-medium">
                                        {{ strtoupper(substr($extension->name, 0, 2)) }}
                                    </div>
                                    @php
                                        $statusColor = match($extension->status) {
                                            'online' => 'bg-green-500',
                                            'on_call' => 'bg-red-500',
                                            'ringing' => 'bg-yellow-500',
                                            default => 'bg-gray-400',
                                        };
                                    @endphp
                                    <span class="absolute bottom-0 right-0 w-3 h-3 {{ $statusColor }} border-2 border-white dark:border-gray-800 rounded-full"></span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $extension->extension }} - {{ $extension->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        @if($extension->user)
                                            {{ $extension->user->name }}
                                        @else
                                            Unassigned
                                        @endif
                                    </p>
                                </div>
                            </div>
                            @php
                                $badgeClass = match($extension->status) {
                                    'online' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
                                    'on_call' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
                                    'ringing' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
                                    default => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400',
                                };
                            @endphp
                            <span class="status-badge px-2 py-1 text-xs font-medium {{ $badgeClass }} rounded-full">
                                {{ ucfirst(str_replace('_', ' ', $extension->status)) }}
                            </span>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <p>No extensions configured</p>
                            <a href="{{ route('extensions.create') }}" class="text-primary-600 dark:text-primary-400 hover:underline mt-2 inline-block">Create Extension</a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Queue Statistics -->
    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Queue Status -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Queue Status</h3>
                <a href="{{ route('queues.index') }}" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">Manage Queues</a>
            </div>
            <div class="p-6">
                @forelse($queues as $queue)
                    @php
                        $colors = ['primary', 'accent', 'purple', 'pink', 'indigo'];
                        $color = $colors[$loop->index % count($colors)];
                    @endphp
                    <div class="mb-6 last:mb-0" data-queue-name="{{ $queue->name }}">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center space-x-3">
                                <div class="w-3 h-3 bg-{{ $color }}-500 rounded-full"></div>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $queue->name }}</span>
                            </div>
                            <div class="flex items-center space-x-6 text-sm">
                                <span class="text-gray-500 dark:text-gray-400">
                                    Waiting: <strong class="text-gray-900 dark:text-white waiting-count">0</strong>
                                </span>
                                <span class="text-gray-500 dark:text-gray-400">
                                    Agents: <strong class="text-gray-900 dark:text-white">{{ $queue->members->count() }}</strong>
                                </span>
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            @php
                                $utilization = $queue->members->count() > 0 ? min(100, ($queue->members->where('paused', false)->count() / $queue->members->count()) * 100) : 0;
                            @endphp
                            <div class="bg-{{ $color }}-600 h-2 rounded-full" style="width: {{ $utilization }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <p>No queues configured</p>
                        <a href="{{ route('queues.create') }}" class="text-primary-600 dark:text-primary-400 hover:underline mt-2 inline-block">Create Queue</a>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Calls -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Calls</h3>
                <a href="{{ route('call-logs.index') }}" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">View All</a>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    @forelse($recentCalls as $call)
                        @php
                            $typeIcon = match($call->type) {
                                'inbound' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>',
                                'outbound' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>',
                                default => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                            };
                            $statusColor = match($call->status) {
                                'answered' => 'text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/30',
                                'missed' => 'text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/30',
                                default => 'text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700',
                            };
                        @endphp
                        <div class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 {{ $call->type === 'inbound' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : 'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400' }} rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        {!! $typeIcon !!}
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $call->caller_id ?: 'Unknown' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $call->start_time?->diffForHumans() }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs font-mono text-gray-500">{{ $call->formatted_duration }}</span>
                                <span class="px-2 py-0.5 text-xs font-medium {{ $statusColor }} rounded">
                                    {{ ucfirst($call->status) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <p>No recent calls</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Online Users Section -->
    <div class="mt-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Users Online</h3>
                <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-medium rounded-full" id="online-users-count">0</span>
            </div>
        </div>
        <div class="p-6">
            <div class="flex flex-wrap gap-3" id="online-users-list">
                <div class="text-gray-500 dark:text-gray-400 text-sm">Loading...</div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    // Alpine.js component for dashboard stats
    function dashboardStats() {
        return {
            activeCalls: {{ $stats['active_calls'] }},
            extensionsOnline: {{ $extensions->where('status', 'online')->count() }},
            
            init() {
                // Listen for extension status changes
                window.addEventListener('extension-status-changed', (e) => {
                    this.updateExtensionCount(e.detail);
                });
                
                // Listen for call events
                window.addEventListener('call-started', () => {
                    this.activeCalls++;
                    document.getElementById('active-calls-count').textContent = this.activeCalls;
                });
                
                window.addEventListener('call-ended', () => {
                    this.activeCalls = Math.max(0, this.activeCalls - 1);
                    document.getElementById('active-calls-count').textContent = this.activeCalls;
                });

                // Poll for live extension status every 5 seconds
                this.pollExtensionStatus();
                setInterval(() => this.pollExtensionStatus(), 5000);

                // Refresh full dashboard data every 5 seconds
                setInterval(() => this.refreshDashboard(), 5000);
            },
            
            updateExtensionCount(data) {
                // Update count based on status change
                if (data.new_status === 'online' && data.previous_status !== 'online') {
                    this.extensionsOnline++;
                } else if (data.previous_status === 'online' && data.new_status !== 'online') {
                    this.extensionsOnline = Math.max(0, this.extensionsOnline - 1);
                }
                document.getElementById('extensions-online-count').textContent = this.extensionsOnline;
            },

            async pollExtensionStatus() {
                try {
                    const response = await fetch('/api/extensions/status');
                    if (!response.ok) return;
                    
                    const data = await response.json();
                    if (!data.success) return;

                    // Update counts
                    this.extensionsOnline = data.summary.online;
                    document.getElementById('extensions-online-count').textContent = data.summary.online;

                    // Update extension status badges
                    data.extensions.forEach(ext => {
                        const row = document.querySelector(`[data-extension-id="${ext.id}"]`);
                        if (row) {
                            const statusDot = row.querySelector('.absolute.bottom-0.right-0');
                            const statusBadge = row.querySelector('.status-badge');
                            
                            // Update status dot color
                            if (statusDot) {
                                statusDot.className = 'absolute bottom-0 right-0 w-3 h-3 border-2 border-white dark:border-gray-800 rounded-full ' + 
                                    (ext.status === 'online' ? 'bg-green-500' : 
                                     ext.status === 'on_call' ? 'bg-red-500' : 
                                     ext.status === 'ringing' ? 'bg-yellow-500' : 'bg-gray-400');
                            }

                            // Update status badge
                            if (statusBadge) {
                                const badgeClass = ext.status === 'online' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' :
                                                   ext.status === 'on_call' ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' :
                                                   ext.status === 'ringing' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400' :
                                                   'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400';
                                statusBadge.className = 'status-badge px-2 py-1 text-xs font-medium rounded-full ' + badgeClass;
                                statusBadge.textContent = ext.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                            }
                        }
                    });

                    // Update online/on-call counts in subtitle
                    const onCallCount = data.extensions.filter(e => e.status === 'on_call').length;
                    const onlineCount = data.extensions.filter(e => e.status === 'online').length;
                    
                } catch (error) {
                    console.log('Failed to poll extension status:', error);
                }
            },

            async refreshDashboard() {
                try {
                    const response = await fetch('/api/dashboard/stats');
                    if (!response.ok) return;
                    
                    const data = await response.json();
                    if (!data.success) return;

                    // Update stats cards
                    document.getElementById('active-calls-count').textContent = data.stats.active_calls;
                    document.getElementById('live-calls-badge').textContent = data.stats.active_calls + ' Active';
                    
                    // Update today's calls if element exists
                    const todaysCallsEl = document.querySelector('[data-stat="todays-calls"]');
                    if (todaysCallsEl) {
                        todaysCallsEl.textContent = data.stats.todays_calls;
                    }

                } catch (error) {
                    console.log('Failed to refresh dashboard:', error);
                }
            }
        }
    }
</script>
