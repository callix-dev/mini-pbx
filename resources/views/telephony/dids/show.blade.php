<x-app-layout>
    @section('title', 'DID ' . $did->did_number)
    @section('page-title', 'DID Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="{{ route('dids.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                        {{ $did->did_number }}
                        <span class="ml-3 badge {{ $did->is_active ? 'badge-success' : 'badge-gray' }}">
                            {{ $did->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $did->description ?: 'No description' }}</p>
                </div>
            </div>
            <a href="{{ route('dids.edit', $did) }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit DID
            </a>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- DID Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">DID Details</h3>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">DID Number</dt>
                            <dd class="mt-1 text-lg font-mono font-bold text-primary-600 dark:text-primary-400">{{ $did->did_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Carrier</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ $did->carrier->name ?? 'Not assigned' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Destination Type</dt>
                            <dd class="mt-1">
                                @php
                                    $destTypes = [
                                        'extension' => 'Extension',
                                        'extension_group' => 'Extension Group',
                                        'queue' => 'Call Queue',
                                        'ring_tree' => 'Ring Tree',
                                        'ivr' => 'IVR',
                                        'voicemail' => 'Voicemail',
                                        'hangup' => 'Hangup',
                                        'block_filter' => 'Block Filter',
                                    ];
                                @endphp
                                <span class="badge badge-primary">
                                    {{ $destTypes[$did->destination_type] ?? $did->destination_type }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Destination</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                @if($did->destination)
                                    {{ $did->destination->name ?? $did->destination->extension ?? 'Unknown' }}
                                @else
                                    N/A
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $did->created_at->format('M d, Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $did->updated_at->format('M d, Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Recent Calls -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Calls</h3>
                    <a href="{{ route('call-logs.index', ['did' => $did->id]) }}" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">
                        View all
                    </a>
                </div>
                <div class="p-6">
                    @if(isset($recentCalls) && $recentCalls->count() > 0)
                        <div class="space-y-4">
                            @foreach($recentCalls as $call)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        @if($call->status === 'answered')
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                        @endif
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $call->source }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $call->created_at->format('M d, H:i') }}</p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-mono text-gray-500 dark:text-gray-400">
                                        {{ gmdate('i:s', $call->duration) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-gray-500 dark:text-gray-400">No recent calls on this DID.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Call Stats -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Today's Stats</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Total Calls</span>
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ $todayStats['total'] ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Answered</span>
                            <span class="text-lg font-semibold text-green-600 dark:text-green-400">{{ $todayStats['answered'] ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Missed</span>
                            <span class="text-lg font-semibold text-red-600 dark:text-red-400">{{ $todayStats['missed'] ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Avg Duration</span>
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ $todayStats['avg_duration'] ?? '0:00' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Routing Flow -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Routing Flow</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Incoming Call</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $did->did_number }}</p>
                            </div>
                        </div>
                        
                        <div class="ml-4 border-l-2 border-gray-200 dark:border-gray-700 pl-4 py-2">
                            <svg class="w-4 h-4 text-gray-400 -ml-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>

                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-accent-100 dark:bg-accent-900/30 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-accent-600 dark:text-accent-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $destTypes[$did->destination_type] ?? $did->destination_type }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $did->destination->name ?? $did->destination->extension ?? 'Not set' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>





