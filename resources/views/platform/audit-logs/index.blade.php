<x-app-layout>
    @section('title', 'Audit Logs')
    @section('page-title', 'Audit Logs')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Audit Logs</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Track all system activities and changes
                </p>
            </div>
            <a href="{{ route('audit-logs.export') }}?{{ http_build_query(request()->query()) }}" class="btn-secondary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export
            </a>
        </div>
    </x-slot>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <form method="GET" action="{{ route('audit-logs.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="user_id" class="form-label">User</label>
                    <select name="user_id" id="user_id" class="form-select">
                        <option value="">All Users</option>
                        @foreach($users ?? [] as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="action" class="form-label">Action</label>
                    <select name="action" id="action" class="form-select">
                        <option value="">All Actions</option>
                        <option value="created" {{ request('action') === 'created' ? 'selected' : '' }}>Created</option>
                        <option value="updated" {{ request('action') === 'updated' ? 'selected' : '' }}>Updated</option>
                        <option value="deleted" {{ request('action') === 'deleted' ? 'selected' : '' }}>Deleted</option>
                        <option value="login" {{ request('action') === 'login' ? 'selected' : '' }}>Login</option>
                        <option value="logout" {{ request('action') === 'logout' ? 'selected' : '' }}>Logout</option>
                        <option value="exported" {{ request('action') === 'exported' ? 'selected' : '' }}>Exported</option>
                        <option value="imported" {{ request('action') === 'imported' ? 'selected' : '' }}>Imported</option>
                    </select>
                </div>
                <div>
                    <label for="model_type" class="form-label">Entity Type</label>
                    <select name="model_type" id="model_type" class="form-select">
                        <option value="">All Types</option>
                        <option value="User" {{ request('model_type') === 'User' ? 'selected' : '' }}>User</option>
                        <option value="Extension" {{ request('model_type') === 'Extension' ? 'selected' : '' }}>Extension</option>
                        <option value="Queue" {{ request('model_type') === 'Queue' ? 'selected' : '' }}>Queue</option>
                        <option value="Did" {{ request('model_type') === 'Did' ? 'selected' : '' }}>DID</option>
                        <option value="Carrier" {{ request('model_type') === 'Carrier' ? 'selected' : '' }}>Carrier</option>
                        <option value="SystemSetting" {{ request('model_type') === 'SystemSetting' ? 'selected' : '' }}>System Setting</option>
                    </select>
                </div>
                <div>
                    <label for="search" class="form-label">Search</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" 
                           class="form-input" placeholder="Search description...">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" 
                           class="form-input">
                </div>
                <div>
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" 
                           class="form-input">
                </div>
                <div class="md:col-span-2 flex items-end gap-3">
                    <button type="submit" class="btn-primary">Filter</button>
                    @if(request()->hasAny(['user_id', 'action', 'model_type', 'search', 'date_from', 'date_to']))
                        <a href="{{ route('audit-logs.index') }}" class="btn-secondary">Clear</a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Audit Logs Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Description</th>
                        <th>IP Address</th>
                        <th class="text-right">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($auditLogs ?? [] as $log)
                        <tr>
                            <td class="whitespace-nowrap">
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ $log->created_at->format('M d, Y') }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $log->created_at->format('H:i:s') }}
                                </div>
                            </td>
                            <td>
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                        <span class="text-xs font-medium text-primary-600 dark:text-primary-400">
                                            {{ $log->user?->initials ?? 'SY' }}
                                        </span>
                                    </div>
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ $log->user?->name ?? 'System' }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @php
                                    $actionColors = [
                                        'created' => 'badge-success',
                                        'updated' => 'badge-info',
                                        'deleted' => 'badge-danger',
                                        'login' => 'badge-primary',
                                        'logout' => 'badge-gray',
                                        'exported' => 'badge-warning',
                                        'imported' => 'badge-accent',
                                    ];
                                @endphp
                                <span class="badge {{ $actionColors[$log->action] ?? 'badge-gray' }}">
                                    {{ ucfirst($log->action) }}
                                </span>
                            </td>
                            <td>
                                <span class="text-gray-900 dark:text-white">
                                    {{ class_basename($log->auditable_type ?? '') }}
                                </span>
                                @if($log->auditable_id)
                                    <span class="text-xs text-gray-500 dark:text-gray-400">#{{ $log->auditable_id }}</span>
                                @endif
                            </td>
                            <td class="max-w-xs">
                                <span class="text-gray-500 dark:text-gray-400 truncate block">
                                    {{ Str::limit($log->description, 40) ?? '-' }}
                                </span>
                            </td>
                            <td>
                                <span class="font-mono text-sm text-gray-500 dark:text-gray-400">
                                    {{ $log->ip_address ?? '-' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('audit-logs.show', $log) }}" 
                                   class="p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 inline-flex"
                                   title="View Details">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No audit logs found</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your filters</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($auditLogs) && $auditLogs->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $auditLogs->withQueryString()->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
