<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('audit-logs.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Audit Log Details
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <!-- Overview Card -->
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="text-lg font-medium">Event Overview</h3>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Timestamp</dt>
                            <dd class="mt-1 text-gray-900 dark:text-white">
                                {{ $auditLog->created_at?->format('F d, Y \a\t H:i:s') ?? '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">User</dt>
                            <dd class="mt-1">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                        <span class="text-xs font-medium text-primary-600 dark:text-primary-400">
                                            {{ $auditLog->user?->initials ?? 'SY' }}
                                        </span>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-gray-900 dark:text-white">{{ $auditLog->user?->name ?? 'System' }}</p>
                                        <p class="text-sm text-gray-500">{{ $auditLog->user?->email ?? '' }}</p>
                                    </div>
                                </div>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Action</dt>
                            <dd class="mt-1">
                                @php
                                    $actionColors = [
                                        'created' => 'badge-success',
                                        'updated' => 'badge-info',
                                        'deleted' => 'badge-danger',
                                        'login' => 'badge-primary',
                                        'logout' => 'badge-gray',
                                    ];
                                @endphp
                                <span class="badge {{ $actionColors[$auditLog->action ?? ''] ?? 'badge-gray' }}">
                                    {{ ucfirst($auditLog->action ?? 'Unknown') }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Entity</dt>
                            <dd class="mt-1 text-gray-900 dark:text-white">
                                {{ class_basename($auditLog->model_type ?? '') }}
                                @if($auditLog->model_id)
                                    <span class="text-gray-500">#{{ $auditLog->model_id }}</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">IP Address</dt>
                            <dd class="mt-1 font-mono text-gray-900 dark:text-white">{{ $auditLog->ip_address ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">User Agent</dt>
                            <dd class="mt-1 text-sm text-gray-600 dark:text-gray-400 break-all">
                                {{ $auditLog->user_agent ?? '-' }}
                            </dd>
                        </div>
                        <div class="md:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
                            <dd class="mt-1 text-gray-900 dark:text-white">{{ $auditLog->description ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Changes Card -->
            @if($auditLog->old_values || $auditLog->new_values)
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-lg font-medium">Changes</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="overflow-x-auto">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Field</th>
                                        <th>Old Value</th>
                                        <th>New Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $oldValues = is_array($auditLog->old_values) ? $auditLog->old_values : json_decode($auditLog->old_values ?? '{}', true) ?? [];
                                        $newValues = is_array($auditLog->new_values) ? $auditLog->new_values : json_decode($auditLog->new_values ?? '{}', true) ?? [];
                                        $allKeys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
                                    @endphp
                                    
                                    @forelse($allKeys as $key)
                                        @if(!in_array($key, ['password', 'remember_token', 'api_token']))
                                            <tr>
                                                <td class="font-medium text-gray-900 dark:text-white">
                                                    {{ Str::title(str_replace('_', ' ', $key)) }}
                                                </td>
                                                <td>
                                                    @if(isset($oldValues[$key]))
                                                        <code class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 rounded text-sm">
                                                            {{ is_array($oldValues[$key]) ? json_encode($oldValues[$key]) : $oldValues[$key] }}
                                                        </code>
                                                    @else
                                                        <span class="text-gray-400 italic">null</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(isset($newValues[$key]))
                                                        <code class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded text-sm">
                                                            {{ is_array($newValues[$key]) ? json_encode($newValues[$key]) : $newValues[$key] }}
                                                        </code>
                                                    @else
                                                        <span class="text-gray-400 italic">null</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                                No changes recorded
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Raw Data (for debugging) -->
            @if(config('app.debug'))
                <div class="card mt-6">
                    <div class="card-header">
                        <h3 class="text-lg font-medium">Raw Data (Debug)</h3>
                    </div>
                    <div class="card-body">
                        <pre class="p-4 bg-gray-800 text-gray-100 rounded-lg overflow-x-auto text-sm">{{ json_encode($auditLog->toArray(), JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>



