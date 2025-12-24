<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Audit Logs
            </h2>
            <a href="{{ route('audit-logs.export') }}?{{ http_build_query(request()->query()) }}" class="btn-secondary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="card mb-6">
                <div class="card-body">
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
                            <div class="md:col-span-2 flex items-end gap-2">
                                <button type="submit" class="btn-primary">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    Filter
                                </button>
                                <a href="{{ route('audit-logs.index') }}" class="btn-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Audit Logs Table -->
            <div class="card">
                <div class="card-body p-0">
                    <div class="overflow-x-auto">
                        <table class="data-table">
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
                                            <div class="text-sm text-gray-900 dark:text-white">
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
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ $log->user?->name ?? 'System' }}
                                                    </p>
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
                                            <span class="text-sm text-gray-900 dark:text-white">
                                                {{ class_basename($log->model_type ?? '') }}
                                            </span>
                                            @if($log->model_id)
                                                <span class="text-xs text-gray-500">#{{ $log->model_id }}</span>
                                            @endif
                                        </td>
                                        <td class="max-w-xs">
                                            <p class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                                {{ $log->description ?? '-' }}
                                            </p>
                                        </td>
                                        <td>
                                            <span class="text-sm font-mono text-gray-500 dark:text-gray-400">
                                                {{ $log->ip_address ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('audit-logs.show', $log) }}" 
                                               class="text-primary-600 hover:text-primary-800 dark:text-primary-400">
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
                                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            <p class="text-gray-500 dark:text-gray-400">No audit logs found</p>
                                            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Try adjusting your filters</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                @if(isset($auditLogs) && $auditLogs->hasPages())
                    <div class="card-footer">
                        {{ $auditLogs->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

