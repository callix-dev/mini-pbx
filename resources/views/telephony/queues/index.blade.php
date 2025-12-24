<x-app-layout>
    @section('title', 'Call Queues')
    @section('page-title', 'Call Queues')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Call Queues</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage call queues and agent assignments
                </p>
            </div>
            <a href="{{ route('queues.create') }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                New Queue
            </a>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($queues ?? [] as $queue)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $queue->name }}</h3>
                        <span class="badge {{ $queue->is_active ? 'badge-success' : 'badge-gray' }}">
                            {{ $queue->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        {{ $queue->description ?: 'No description' }}
                    </p>

                    <div class="grid grid-cols-3 gap-4 text-center mb-4">
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $queue->waiting_count ?? 0 }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Waiting</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $queue->agents_available ?? 0 }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Available</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $queue->members_count ?? $queue->members->count() }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Agents</p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-4">
                        <span>Strategy:</span>
                        <span class="badge badge-gray">{{ ucfirst(str_replace('_', ' ', $queue->strategy)) }}</span>
                    </div>
                </div>
                
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <a href="{{ route('queues.show', $queue) }}" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">
                        View Details
                    </a>
                    <div class="flex items-center space-x-2">
                        <a href="{{ route('queues.edit', $queue) }}" 
                           class="p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600"
                           title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                        <form action="{{ route('queues.destroy', $queue) }}" method="POST" class="inline" 
                              onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600"
                                    title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No queues configured</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new call queue.</p>
                    <div class="mt-6">
                        <a href="{{ route('queues.create') }}" class="btn-primary">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            New Queue
                        </a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</x-app-layout>

