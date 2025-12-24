<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Call Dispositions
            </h2>
            <a href="{{ route('dispositions.create') }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Disposition
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            <!-- Info Banner -->
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                <div class="flex">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-green-800 dark:text-green-200">About Dispositions</h3>
                        <p class="text-sm text-green-700 dark:text-green-300 mt-1">
                            Dispositions allow agents to categorize call outcomes. Some dispositions can require callback scheduling. 
                            Drag rows to reorder how they appear in the agent interface.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Dispositions Table -->
            <div class="card">
                <div class="card-body p-0">
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th class="w-12">#</th>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Color</th>
                                    <th>Callback Required</th>
                                    <th>Status</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="sortable-dispositions">
                                @forelse($dispositions ?? [] as $disposition)
                                    <tr data-id="{{ $disposition->id }}" class="cursor-move">
                                        <td class="text-gray-500">
                                            <svg class="w-5 h-5 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                                            </svg>
                                        </td>
                                        <td>
                                            <div class="flex items-center">
                                                <span class="w-3 h-3 rounded-full mr-3" style="background-color: {{ $disposition->color }}"></span>
                                                <span class="font-medium text-gray-900 dark:text-white">{{ $disposition->name }}</span>
                                                @if($disposition->is_default)
                                                    <span class="ml-2 badge badge-primary text-xs">Default</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="font-mono text-sm text-gray-600 dark:text-gray-400">{{ $disposition->code }}</span>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <span class="w-6 h-6 rounded border border-gray-200 dark:border-gray-600" 
                                                      style="background-color: {{ $disposition->color }}"></span>
                                                <span class="text-sm text-gray-500 font-mono">{{ $disposition->color }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if($disposition->requires_callback)
                                                <span class="badge badge-warning">Yes</span>
                                            @else
                                                <span class="badge badge-gray">No</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($disposition->is_active)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('dispositions.edit', $disposition) }}" 
                                                   class="text-primary-600 hover:text-primary-900 dark:text-primary-400">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </a>
                                                @if(!$disposition->is_default)
                                                    <form action="{{ route('dispositions.destroy', $disposition) }}" method="POST" class="inline"
                                                          onsubmit="return confirm('Delete this disposition?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-12">
                                            <div class="flex flex-col items-center">
                                                <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                                </svg>
                                                <p class="text-gray-500 dark:text-gray-400 mb-4">No dispositions found</p>
                                                <a href="{{ route('dispositions.create') }}" class="btn-primary">
                                                    Create your first disposition
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Color Legend -->
            <div class="mt-6 card">
                <div class="card-header">
                    <h3 class="text-lg font-medium">Quick Reference</h3>
                </div>
                <div class="card-body">
                    <div class="flex flex-wrap gap-4">
                        @foreach($dispositions ?? [] as $disposition)
                            <div class="flex items-center gap-2 px-3 py-2 rounded-lg" 
                                 style="background-color: {{ $disposition->color }}20;">
                                <span class="w-3 h-3 rounded-full" style="background-color: {{ $disposition->color }}"></span>
                                <span class="text-sm font-medium" style="color: {{ $disposition->color }}">{{ $disposition->name }}</span>
                                <span class="text-xs text-gray-500">({{ $disposition->code }})</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

