<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('queues.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Queue: {{ $queue->name ?? 'Unknown' }}
                </h2>
                @if($queue->is_active ?? false)
                    <span class="badge badge-success">Active</span>
                @else
                    <span class="badge badge-danger">Inactive</span>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('queues.edit', $queue ?? 0) }}" class="btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Information -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="text-lg font-medium">Basic Information</h3>
                        </div>
                        <div class="card-body">
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Queue Name</dt>
                                    <dd class="mt-1 text-gray-900 dark:text-white">{{ $queue->name ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Queue Number</dt>
                                    <dd class="mt-1 text-gray-900 dark:text-white font-mono">{{ $queue->number ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Strategy</dt>
                                    <dd class="mt-1 text-gray-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $queue->strategy ?? 'ringall')) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                    <dd class="mt-1">
                                        @if($queue->is_active ?? false)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </dd>
                                </div>
                                <div class="md:col-span-2">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
                                    <dd class="mt-1 text-gray-900 dark:text-white">{{ $queue->description ?? 'No description' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Queue Members -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="text-lg font-medium">Queue Members</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Extension</th>
                                        <th>Name</th>
                                        <th>Status</th>
                                        <th>Penalty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($queue->members ?? [] as $member)
                                        <tr>
                                            <td class="font-mono">{{ $member->extension->extension ?? '-' }}</td>
                                            <td>{{ $member->extension->name ?? '-' }}</td>
                                            <td>
                                                @if($member->is_paused)
                                                    <span class="badge badge-warning">Paused</span>
                                                @else
                                                    <span class="badge badge-success">Active</span>
                                                @endif
                                            </td>
                                            <td>{{ $member->penalty ?? 0 }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                                No members assigned to this queue
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- Real-time Stats -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="text-lg font-medium">Real-time Stats</h3>
                        </div>
                        <div class="card-body space-y-4">
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <span class="text-gray-600 dark:text-gray-400">Calls Waiting</span>
                                <span class="text-2xl font-bold text-primary-600 dark:text-primary-400">0</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <span class="text-gray-600 dark:text-gray-400">Active Calls</span>
                                <span class="text-2xl font-bold text-green-600 dark:text-green-400">0</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <span class="text-gray-600 dark:text-gray-400">Agents Available</span>
                                <span class="text-2xl font-bold text-accent-600 dark:text-accent-400">{{ ($queue->members ?? collect())->count() }}</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <span class="text-gray-600 dark:text-gray-400">Avg Wait Time</span>
                                <span class="text-2xl font-bold text-gray-900 dark:text-white">0:00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Settings -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="text-lg font-medium">Settings</h3>
                        </div>
                        <div class="card-body space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Ring Timeout</span>
                                <span class="text-gray-900 dark:text-white">{{ $queue->timeout ?? 30 }}s</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Wrap-up Time</span>
                                <span class="text-gray-900 dark:text-white">{{ $queue->wrapuptime ?? 0 }}s</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Max Wait Time</span>
                                <span class="text-gray-900 dark:text-white">{{ $queue->max_wait_time ?? 300 }}s</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Hold Music</span>
                                <span class="text-gray-900 dark:text-white">{{ $queue->holdMusic->name ?? 'Default' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>







