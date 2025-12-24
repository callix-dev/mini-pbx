<x-app-layout>
    @section('title', 'Extension ' . $extension->extension)
    @section('page-title', 'Extension Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="{{ route('extensions.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                        Extension {{ $extension->extension }}
                        @php
                            $statusColors = [
                                'online' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                'offline' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400',
                                'ringing' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                'on_call' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                            ];
                        @endphp
                        <span class="ml-3 px-2.5 py-0.5 text-sm rounded-full {{ $statusColors[$extension->status] ?? $statusColors['offline'] }}">
                            {{ ucfirst(str_replace('_', ' ', $extension->status)) }}
                        </span>
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $extension->name }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('extensions.edit', $extension) }}" class="btn-secondary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>
                <form action="{{ route('extensions.toggle-status', $extension) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="{{ $extension->is_active ? 'btn-danger' : 'btn-success' }}">
                        @if($extension->is_active)
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                            Disable
                        @else
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Enable
                        @endif
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Extension Info -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Extension Details</h3>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Extension</dt>
                            <dd class="mt-1 text-lg font-mono font-bold text-primary-600 dark:text-primary-400">{{ $extension->extension }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $extension->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Assigned User</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                @if($extension->user)
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-primary-600 rounded-full flex items-center justify-center text-white text-sm font-medium mr-2">
                                            {{ strtoupper(substr($extension->user->name, 0, 2)) }}
                                        </div>
                                        {{ $extension->user->name }}
                                    </div>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">Unassigned</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                            <dd class="mt-1">
                                <span class="badge {{ $extension->is_active ? 'badge-success' : 'badge-danger' }}">
                                    {{ $extension->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Caller ID Name</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $extension->caller_id_name ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Caller ID Number</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $extension->caller_id_number ?: '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Voicemail Settings -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Voicemail Settings</h3>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Voicemail Enabled</dt>
                            <dd class="mt-1">
                                <span class="badge {{ $extension->voicemail_enabled ? 'badge-success' : 'badge-gray' }}">
                                    {{ $extension->voicemail_enabled ? 'Yes' : 'No' }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Voicemail Email</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $extension->voicemail_email ?: '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Extension Groups -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Extension Groups</h3>
                    <span class="badge badge-gray">{{ $extension->groups->count() }} groups</span>
                </div>
                <div class="p-6">
                    @if($extension->groups->count() > 0)
                        <div class="flex flex-wrap gap-2">
                            @foreach($extension->groups as $group)
                                <a href="{{ route('extension-groups.show', $group) }}" 
                                   class="px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                    {{ $group->name }}
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-sm">This extension is not part of any groups.</p>
                    @endif
                </div>
            </div>

            <!-- Queue Memberships -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Queue Memberships</h3>
                    <span class="badge badge-gray">{{ $extension->queueMemberships->count() }} queues</span>
                </div>
                <div class="p-6">
                    @if($extension->queueMemberships->count() > 0)
                        <div class="space-y-3">
                            @foreach($extension->queueMemberships as $membership)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <div>
                                        <a href="{{ route('queues.show', $membership->queue) }}" 
                                           class="font-medium text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400">
                                            {{ $membership->queue->name }}
                                        </a>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Priority: {{ $membership->priority }} · Penalty: {{ $membership->penalty }}
                                        </p>
                                    </div>
                                    <span class="badge {{ $membership->is_paused ? 'badge-warning' : 'badge-success' }}">
                                        {{ $membership->is_paused ? 'Paused' : 'Active' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-sm">This extension is not a member of any queues.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Quick Actions</h3>
                </div>
                <div class="p-4 space-y-2">
                    <button type="button" class="w-full btn-secondary text-left">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        Call Extension
                    </button>
                    <form action="{{ route('extensions.email-credentials', $extension) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full btn-secondary text-left">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Email Credentials
                        </button>
                    </form>
                </div>
            </div>

            <!-- Recent Voicemails -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Voicemails</h3>
                    @if($extension->voicemails && $extension->voicemails->count() > 0)
                        <span class="badge badge-accent">{{ $extension->voicemails->where('is_read', false)->count() }} new</span>
                    @endif
                </div>
                <div class="p-4">
                    @if($extension->voicemails && $extension->voicemails->count() > 0)
                        <div class="space-y-3">
                            @foreach($extension->voicemails->take(5) as $voicemail)
                                <div class="flex items-center justify-between p-2 rounded-lg {{ $voicemail->is_read ? '' : 'bg-accent-50 dark:bg-accent-900/20' }}">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $voicemail->caller_id }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $voicemail->created_at->diffForHumans() }}</p>
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ gmdate('i:s', $voicemail->duration) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-sm">No voicemails yet.</p>
                    @endif
                </div>
            </div>

            <!-- Recent Calls -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Calls</h3>
                </div>
                <div class="p-4">
                    @if($recentCalls->count() > 0)
                        <div class="space-y-3">
                            @foreach($recentCalls->take(10) as $call)
                                <div class="flex items-center justify-between p-2">
                                    <div class="flex items-center space-x-3">
                                        @if($call->type === 'inbound')
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                        @elseif($call->type === 'outbound')
                                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                        @endif
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $call->type === 'inbound' ? $call->source : $call->destination }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $call->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-mono text-gray-500 dark:text-gray-400">
                                        {{ gmdate('i:s', $call->duration) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-sm">No recent calls.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

