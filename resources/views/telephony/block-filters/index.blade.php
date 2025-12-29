<x-app-layout>
    @section('title', 'Block Filters')
    @section('page-title', 'Block Filters')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Block Filters</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage blacklist and whitelist rules
                </p>
            </div>
            <a href="{{ route('block-filters.create') }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Filter
            </a>
        </div>
    </x-slot>

    <!-- Filter Groups -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
        @forelse($filterGroups ?? [] as $group)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $group->name }}</h3>
                        <span class="badge {{ $group->type === 'blacklist' ? 'badge-danger' : 'badge-success' }}">
                            {{ ucfirst($group->type) }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ $group->description ?: 'No description' }}</p>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">{{ $group->filters_count ?? $group->filters->count() }} numbers</span>
                        <span class="badge {{ $group->is_active ? 'badge-success' : 'badge-gray' }}">
                            {{ $group->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end space-x-2">
                    <a href="{{ route('block-filters.show', $group) }}" class="btn-secondary text-sm py-1.5 px-3">View</a>
                    <a href="{{ route('block-filters.edit', $group) }}" class="btn-secondary text-sm py-1.5 px-3">Edit</a>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No filter groups</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a filter group.</p>
                    <div class="mt-6">
                        <a href="{{ route('block-filters.create') }}" class="btn-primary">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Add Filter
                        </a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Recent Blocks -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">All Filters</h3>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Number/Pattern</th>
                        <th>Group</th>
                        <th>Type</th>
                        <th>Reason</th>
                        <th>Expires</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($filters ?? [] as $filter)
                        <tr>
                            <td>
                                <span class="font-mono text-gray-900 dark:text-white">{{ $filter->number }}</span>
                            </td>
                            <td>{{ $filter->group->name ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $filter->group?->type === 'blacklist' ? 'badge-danger' : 'badge-success' }}">
                                    {{ ucfirst($filter->group?->type ?? 'unknown') }}
                                </span>
                            </td>
                            <td>
                                <span class="text-gray-500 dark:text-gray-400">{{ $filter->reason ?? '-' }}</span>
                            </td>
                            <td>
                                @if($filter->expires_at)
                                    <span class="{{ $filter->expires_at->isPast() ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
                                        {{ $filter->expires_at->format('M d, Y') }}
                                    </span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">Never</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <form action="{{ route('block-filters.destroy', $filter) }}" method="POST" class="inline" 
                                      onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                No filters configured yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($filters) && $filters->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $filters->withQueryString()->links() }}
            </div>
        @endif
    </div>
</x-app-layout>







