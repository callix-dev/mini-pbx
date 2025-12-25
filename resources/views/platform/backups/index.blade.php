<x-app-layout>
    @section('title', 'Backups')
    @section('page-title', 'Backups')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Backups</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage system backups and restore points
                </p>
            </div>
            <form action="{{ route('backups.create') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="btn-primary" onclick="return confirm('Create a new backup now?')">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Create Backup
                </button>
            </form>
        </div>
    </x-slot>

    <!-- Info Banner -->
    <div class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
        <div class="flex">
            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <h3 class="text-sm font-medium text-amber-800 dark:text-amber-200">Backup Information</h3>
                <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                    Backups include the database and Asterisk configuration files. Restore operations will overwrite current configurations. 
                    Always test backups in a staging environment first.
                </p>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 text-center">
            <div class="w-12 h-12 mx-auto rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ ($backups ?? collect())->count() }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Backups</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 text-center">
            <div class="w-12 h-12 mx-auto rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ ($backups ?? collect())->first()?->created_at?->diffForHumans() ?? 'Never' }}
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Last Backup</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 text-center">
            <div class="w-12 h-12 mx-auto rounded-full bg-accent-100 dark:bg-accent-900 flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-accent-600 dark:text-accent-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                </svg>
            </div>
            @php
                $totalSize = ($backups ?? collect())->sum('size');
                $sizeFormatted = $totalSize > 1073741824 
                    ? number_format($totalSize / 1073741824, 2) . ' GB'
                    : number_format($totalSize / 1048576, 2) . ' MB';
            @endphp
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $sizeFormatted }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Size</p>
        </div>
    </div>

    <!-- Backups Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Backup History</h3>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Created</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Size</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backups ?? [] as $backup)
                        <tr>
                            <td class="whitespace-nowrap">
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ $backup->created_at->format('M d, Y') }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $backup->created_at->format('H:i:s') }}
                                </div>
                            </td>
                            <td>
                                <div class="flex items-center">
                                    <svg class="w-8 h-8 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                    </svg>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $backup->name }}</p>
                                        <p class="text-xs text-gray-500 font-mono">{{ $backup->filename }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($backup->type === 'full')
                                    <span class="badge badge-primary">Full</span>
                                @elseif($backup->type === 'database')
                                    <span class="badge badge-info">Database</span>
                                @else
                                    <span class="badge badge-gray">{{ ucfirst($backup->type) }}</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $size = $backup->size ?? 0;
                                    $sizeStr = $size > 1073741824 
                                        ? number_format($size / 1073741824, 2) . ' GB'
                                        : number_format($size / 1048576, 2) . ' MB';
                                @endphp
                                <span class="text-gray-900 dark:text-white">{{ $sizeStr }}</span>
                            </td>
                            <td>
                                @if($backup->status === 'completed')
                                    <span class="badge badge-success">Completed</span>
                                @elseif($backup->status === 'in_progress')
                                    <span class="badge badge-warning flex items-center">
                                        <svg class="w-3 h-3 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        In Progress
                                    </span>
                                @elseif($backup->status === 'failed')
                                    <span class="badge badge-danger">Failed</span>
                                @else
                                    <span class="badge badge-gray">{{ ucfirst($backup->status) }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-gray-500 dark:text-gray-400">{{ $backup->createdBy?->name ?? 'System' }}</span>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    @if($backup->status === 'completed')
                                        <a href="{{ route('backups.download', $backup) }}" 
                                           class="p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                           title="Download">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                        </a>
                                        <form action="{{ route('backups.restore', $backup) }}" method="POST" class="inline"
                                              onsubmit="return confirm('WARNING: This will restore your system to this backup state. All current data will be overwritten. Are you sure?')">
                                            @csrf
                                            <button type="submit" 
                                                    class="p-2 text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                                    title="Restore">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                    <form action="{{ route('backups.destroy', $backup) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Delete this backup permanently?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                                title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No backups yet</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Create your first backup to protect your data.</p>
                                <div class="mt-6">
                                    <form action="{{ route('backups.create') }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="btn-primary">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                            </svg>
                                            Create First Backup
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($backups) && $backups->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $backups->links() }}
            </div>
        @endif
    </div>

    <!-- Backup Schedule Info -->
    <div class="mt-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Automated Backups</h3>
        </div>
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-medium text-gray-900 dark:text-white">Daily Automated Backups</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Backups are automatically created daily at midnight (server time). Configure backup retention in 
                        <a href="{{ route('system-settings.index') }}" class="text-primary-600 hover:text-primary-800 dark:text-primary-400">System Settings</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
