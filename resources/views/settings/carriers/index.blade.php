<x-app-layout>
    @section('title', 'Carriers')
    @section('page-title', 'Carriers')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Carriers</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage outbound and inbound SIP carriers
                </p>
            </div>
            <a href="{{ route('carriers.create') }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                New Carrier
            </a>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse($carriers ?? [] as $carrier)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $carrier->name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $carrier->host }}:{{ $carrier->port }}</p>
                            </div>
                        </div>
                        <span class="badge {{ $carrier->is_active ? 'badge-success' : 'badge-gray' }}">
                            {{ $carrier->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Type:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ ucfirst($carrier->type) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Protocol:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ strtoupper($carrier->transport) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Username:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $carrier->username ?: '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Priority:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $carrier->priority }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end space-x-2">
                    <form action="{{ route('carriers.toggle-status', $carrier) }}" method="POST" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn-secondary text-sm py-1.5 px-3">
                            {{ $carrier->is_active ? 'Disable' : 'Enable' }}
                        </button>
                    </form>
                    <a href="{{ route('carriers.edit', $carrier) }}" class="btn-secondary text-sm py-1.5 px-3">Edit</a>
                    <form action="{{ route('carriers.destroy', $carrier) }}" method="POST" class="inline" 
                          onsubmit="return confirm('Are you sure?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger text-sm py-1.5 px-3">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No carriers configured</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add carriers to enable inbound and outbound calling.</p>
                    <div class="mt-6">
                        <a href="{{ route('carriers.create') }}" class="btn-primary">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            New Carrier
                        </a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</x-app-layout>

