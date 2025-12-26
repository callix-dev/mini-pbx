<x-app-layout>
    @section('title', 'DIDs')
    @section('page-title', 'DIDs')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">DIDs</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage Direct Inward Dialing numbers and routing
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('dids.create') }}" class="btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    New DID
                </a>
                <button type="button" onclick="document.getElementById('import-file').click()" class="btn-secondary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Import
                </button>
                <form action="{{ route('dids.import') }}" method="POST" enctype="multipart/form-data" class="hidden">
                    @csrf
                    <input type="file" id="import-file" name="file" accept=".csv,.xlsx" onchange="this.form.submit()">
                </form>
            </div>
        </div>
    </x-slot>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
        <div class="p-4">
            <form method="GET" class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Search DIDs..."
                           class="form-input">
                </div>
                <div class="w-40">
                    <select name="destination_type" class="form-select">
                        <option value="">All Destinations</option>
                        <option value="extension" {{ request('destination_type') === 'extension' ? 'selected' : '' }}>Extension</option>
                        <option value="extension_group" {{ request('destination_type') === 'extension_group' ? 'selected' : '' }}>Extension Group</option>
                        <option value="queue" {{ request('destination_type') === 'queue' ? 'selected' : '' }}>Queue</option>
                        <option value="ring_tree" {{ request('destination_type') === 'ring_tree' ? 'selected' : '' }}>Ring Tree</option>
                        <option value="ivr" {{ request('destination_type') === 'ivr' ? 'selected' : '' }}>IVR</option>
                        <option value="voicemail" {{ request('destination_type') === 'voicemail' ? 'selected' : '' }}>Voicemail</option>
                        <option value="hangup" {{ request('destination_type') === 'hangup' ? 'selected' : '' }}>Hangup</option>
                    </select>
                </div>
                <button type="submit" class="btn-secondary">Filter</button>
                @if(request()->hasAny(['search', 'destination_type']))
                    <a href="{{ route('dids.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">Clear</a>
                @endif
            </form>
        </div>
    </div>

    <!-- DIDs Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>DID Number</th>
                        <th>Description</th>
                        <th>Carrier</th>
                        <th>Destination</th>
                        <th>Active</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dids ?? [] as $did)
                        <tr>
                            <td>
                                <span class="font-mono font-medium text-primary-600 dark:text-primary-400">
                                    {{ $did->did_number }}
                                </span>
                            </td>
                            <td>
                                <span class="text-gray-500 dark:text-gray-400">{{ $did->description ?: '-' }}</span>
                            </td>
                            <td>
                                @if($did->carrier)
                                    <span class="text-gray-900 dark:text-white">{{ $did->carrier->name }}</span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $destTypes = [
                                        'extension' => 'Extension',
                                        'extension_group' => 'Group',
                                        'queue' => 'Queue',
                                        'ring_tree' => 'Ring Tree',
                                        'ivr' => 'IVR',
                                        'voicemail' => 'Voicemail',
                                        'hangup' => 'Hangup',
                                        'block_filter' => 'Block Filter',
                                    ];
                                @endphp
                                <span class="badge badge-gray">
                                    {{ $destTypes[$did->destination_type] ?? $did->destination_type }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $did->is_active ? 'badge-success' : 'badge-gray' }}">
                                    {{ $did->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('dids.show', $did) }}" 
                                       class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                       title="View">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('dids.edit', $did) }}" 
                                       class="p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                       title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('dids.destroy', $did) }}" method="POST" class="inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this DID?')">
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
                            <td colspan="6" class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No DIDs configured</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by adding a new DID.</p>
                                <div class="mt-6">
                                    <a href="{{ route('dids.create') }}" class="btn-primary">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                        New DID
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($dids) && $dids->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $dids->withQueryString()->links() }}
            </div>
        @endif
    </div>
</x-app-layout>


