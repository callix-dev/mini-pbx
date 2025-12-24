<x-app-layout>
    @section('title', 'Call Dispositions')
    @section('page-title', 'Call Dispositions')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Call Dispositions</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage call outcome categories for agents
                </p>
            </div>
            <a href="{{ route('dispositions.create') }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                New Disposition
            </a>
        </div>
    </x-slot>

    <!-- Info Banner -->
    <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
        <div class="flex">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">About Dispositions</h3>
                <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                    Dispositions allow agents to categorize call outcomes. Some dispositions can require callback scheduling. 
                    <strong>Drag rows to reorder</strong> how they appear in the agent interface.
                </p>
            </div>
        </div>
    </div>

    <!-- Dispositions Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th class="w-10"></th>
                        <th class="w-14 text-center">#</th>
                        <th>Name</th>
                        <th class="w-24">Code</th>
                        <th class="w-32">Color</th>
                        <th class="w-36 text-center">Callback</th>
                        <th class="w-24 text-center">Status</th>
                        <th class="w-24 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="sortable-dispositions">
                    @forelse($dispositions ?? [] as $index => $disposition)
                        <tr data-id="{{ $disposition->id }}" class="sortable-row">
                            <td class="cursor-move drag-handle">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                                </svg>
                            </td>
                            <td class="text-center">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-medium" 
                                      style="background-color: {{ $disposition->color }}20; color: {{ $disposition->color }}">
                                    {{ $index + 1 }}
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center">
                                    <span class="w-3 h-3 rounded-full mr-3 flex-shrink-0" style="background-color: {{ $disposition->color }}"></span>
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
                                <div class="flex items-center space-x-2">
                                    <span class="w-6 h-6 rounded border border-gray-200 dark:border-gray-600 flex-shrink-0" 
                                          style="background-color: {{ $disposition->color }}"></span>
                                    <span class="text-xs text-gray-500 font-mono">{{ $disposition->color }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                @if($disposition->requires_callback)
                                    <span class="badge badge-warning">Yes</span>
                                @else
                                    <span class="text-gray-400">No</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $disposition->is_active ? 'badge-success' : 'badge-gray' }}">
                                    {{ $disposition->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('dispositions.edit', $disposition) }}" 
                                       class="p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                       title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    @if(!$disposition->is_default)
                                        <form action="{{ route('dispositions.destroy', $disposition) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Delete this disposition?')">
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
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No dispositions</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new disposition.</p>
                                <div class="mt-6">
                                    <a href="{{ route('dispositions.create') }}" class="btn-primary">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                        New Disposition
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($dispositions) && $dispositions->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $dispositions->links() }}
            </div>
        @endif
    </div>

    <!-- Color Legend -->
    <div class="mt-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Reference</h3>
        </div>
        <div class="p-6">
            <div class="flex flex-wrap gap-3">
                @foreach($dispositions ?? [] as $disposition)
                    <div class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700" 
                         style="background-color: {{ $disposition->color }}10;">
                        <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $disposition->color }}"></span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $disposition->name }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">({{ $disposition->code }})</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- SortableJS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tbody = document.getElementById('sortable-dispositions');
            if (!tbody) return;

            new Sortable(tbody, {
                animation: 150,
                handle: '.drag-handle',
                ghostClass: 'bg-primary-50 dark:bg-primary-900/20',
                chosenClass: 'bg-gray-50 dark:bg-gray-700',
                dragClass: 'shadow-lg',
                onEnd: function(evt) {
                    // Get all row IDs in new order
                    const rows = tbody.querySelectorAll('tr[data-id]');
                    const ids = Array.from(rows).map(row => row.dataset.id);

                    // Update row numbers visually
                    rows.forEach((row, index) => {
                        const numberSpan = row.querySelector('td:nth-child(2) span');
                        if (numberSpan) {
                            numberSpan.textContent = index + 1;
                        }
                    });

                    // Send to server
                    fetch('{{ route('dispositions.reorder') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ ids: ids })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Optional: show success toast
                            console.log('Order saved successfully');
                        }
                    })
                    .catch(error => {
                        console.error('Error saving order:', error);
                        alert('Failed to save order. Please refresh and try again.');
                    });
                }
            });
        });
    </script>

    <style>
        .sortable-row {
            transition: background-color 0.15s ease;
        }
        .sortable-row:hover .drag-handle {
            color: #6366f1;
        }
        .drag-handle {
            cursor: grab;
        }
        .drag-handle:active {
            cursor: grabbing;
        }
    </style>
</x-app-layout>
