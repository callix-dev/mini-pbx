<x-app-layout>
    @section('title', $extensionGroup->name . ' - Extension Group')
    @section('page-title', 'Extension Group')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('extension-groups.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200 flex items-center">
                        {{ $extensionGroup->name }}
                        @if($extensionGroup->group_number)
                            <code class="ml-3 px-2 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 rounded font-mono text-sm">
                                *6{{ $extensionGroup->group_number }}
                            </code>
                        @endif
                        <span class="ml-3 badge {{ $extensionGroup->is_active ? 'badge-success' : 'badge-gray' }}">
                            {{ $extensionGroup->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $extensionGroup->description ?: 'No description' }}</p>
                </div>
            </div>
            <a href="{{ route('extension-groups.edit', $extensionGroup) }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit Group
            </a>
        </div>
    </x-slot>

    <div x-data="extensionGroupStatus({{ $extensionGroup->id }})" x-init="init()">
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Calls</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($extensionGroup->total_calls) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Answered</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($extensionGroup->answered_calls) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Missed</p>
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($extensionGroup->missed_calls) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Answer Rate</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $extensionGroup->answer_rate }}%</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <!-- Group Info -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Group Settings</h3>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ring Strategy</dt>
                                <dd class="mt-1">
                                    <span class="badge badge-primary">{{ $extensionGroup->ring_strategy_label }}</span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ring Timeout</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $extensionGroup->ring_time }} seconds</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Pickup Group</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    @if($extensionGroup->pickup_group)
                                        <span class="font-mono">{{ $extensionGroup->pickup_group }}</span>
                                        <span class="text-xs text-gray-500">(dial *8 to pickup)</span>
                                    @else
                                        <span class="text-gray-400">Not set</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Music on Hold</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ ucfirst($extensionGroup->music_on_hold ?? 'default') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Record Calls</dt>
                                <dd class="mt-1 text-sm">
                                    @if($extensionGroup->record_calls)
                                        <span class="text-green-600 dark:text-green-400">Yes</span>
                                    @else
                                        <span class="text-gray-400">No</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Avg Talk Time</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $extensionGroup->formatted_avg_talk_time }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Members with Live Status -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Group Members</h3>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-gray-500">Live</span>
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                            </span>
                        </div>
                    </div>
                    <div class="p-6">
                        @if($extensionGroup->extensions->count() > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($extensionGroup->extensions as $index => $extension)
                                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                                        <div class="flex items-center space-x-3">
                                            <div class="relative">
                                                <div class="w-10 h-10 bg-gradient-to-br from-primary-400 to-accent-500 rounded-lg flex items-center justify-center text-white font-medium text-sm">
                                                    {{ strtoupper(substr($extension->name, 0, 2)) }}
                                                </div>
                                                <span class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white dark:border-gray-700 flex items-center justify-center text-[8px] font-bold text-white
                                                    {{ $extension->status === 'online' ? 'bg-green-500' : ($extension->status === 'on_call' ? 'bg-red-500' : ($extension->status === 'ringing' ? 'bg-yellow-500' : 'bg-gray-400')) }}">
                                                    {{ $index + 1 }}
                                                </span>
                                            </div>
                                            <div>
                                                <a href="{{ route('extensions.show', $extension) }}" class="font-mono font-medium text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400">
                                                    {{ $extension->extension }}
                                                </a>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $extension->name }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            @php
                                                $statusConfig = [
                                                    'online' => ['label' => 'Online', 'class' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'],
                                                    'on_call' => ['label' => 'On Call', 'class' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'],
                                                    'ringing' => ['label' => 'Ringing', 'class' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'],
                                                    'offline' => ['label' => 'Offline', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'],
                                                ];
                                                $config = $statusConfig[$extension->status] ?? $statusConfig['offline'];
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $config['class'] }}">
                                                {{ $config['label'] }}
                                            </span>
                                            @if($extension->last_registered_at)
                                                <p class="text-[10px] text-gray-400 mt-1">{{ $extension->last_registered_at->diffForHumans() }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-center text-gray-500 dark:text-gray-400 py-8">
                                No extensions in this group. 
                                <a href="{{ route('extension-groups.edit', $extensionGroup) }}" class="text-primary-600 hover:underline">Add members</a>
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Live Status Summary -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Live Status</h3>
                    </div>
                    <div class="p-6">
                        @php $stats = $extensionGroup->live_stats ?? $extensionGroup->member_status_counts; @endphp
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Online</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="stats.online">{{ $stats['online'] ?? 0 }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="w-3 h-3 bg-red-500 rounded-full mr-2"></span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">On Call</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="stats.on_call">{{ $stats['on_call'] ?? 0 }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Ringing</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="stats.ringing">{{ $stats['ringing'] ?? 0 }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="w-3 h-3 bg-gray-400 rounded-full mr-2"></span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Offline</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="stats.offline">{{ $stats['offline'] ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Quick Actions</h3>
                    </div>
                    <div class="p-4 space-y-2">
                        @if($extensionGroup->group_number)
                            <button onclick="copyToClipboard('*6{{ $extensionGroup->group_number }}')" 
                                    class="w-full flex items-center justify-between px-4 py-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Copy Dial Code</span>
                                <code class="font-mono text-primary-600 dark:text-primary-400">*6{{ $extensionGroup->group_number }}</code>
                            </button>
                        @endif
                        <form action="{{ route('extension-groups.reset-stats', $extensionGroup) }}" method="POST" 
                              onsubmit="return confirm('Are you sure you want to reset all statistics for this group?')">
                            @csrf
                            <button type="submit" class="w-full flex items-center justify-between px-4 py-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 transition-colors">
                                <span class="text-sm">Reset Statistics</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Info -->
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800 p-4">
                    <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-2">Feature Codes</h4>
                    <ul class="text-xs text-blue-700 dark:text-blue-400 space-y-1">
                        @if($extensionGroup->group_number)
                            <li><code class="bg-blue-100 dark:bg-blue-800/50 px-1 rounded">*6{{ $extensionGroup->group_number }}</code> - Dial this group</li>
                        @endif
                        @if($extensionGroup->pickup_group)
                            <li><code class="bg-blue-100 dark:bg-blue-800/50 px-1 rounded">*8</code> - Pickup ringing call in group</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        function extensionGroupStatus(groupId) {
            return {
                stats: { online: 0, on_call: 0, ringing: 0, offline: 0 },
                members: [],
                
                init() {
                    this.pollStatus();
                    setInterval(() => this.pollStatus(), 5000);
                },
                
                async pollStatus() {
                    try {
                        const response = await fetch(`/telephony/extension-groups/${groupId}/live-status`);
                        if (response.ok) {
                            const data = await response.json();
                            if (data.success) {
                                this.stats = data.group.stats;
                                this.members = data.members;
                            }
                        }
                    } catch (e) {
                        console.error('Failed to poll group status:', e);
                    }
                }
            }
        }
        
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Copied: ' + text);
            });
        }
    </script>
</x-app-layout>
