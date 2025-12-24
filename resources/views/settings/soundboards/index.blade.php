<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Soundboards
            </h2>
            <a href="{{ route('soundboards.create') }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Create Soundboard
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Info Banner -->
            <div class="mb-6 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                <div class="flex">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-purple-800 dark:text-purple-200">About Soundboards</h3>
                        <p class="text-sm text-purple-700 dark:text-purple-300 mt-1">
                            Soundboards allow agents to play pre-recorded audio clips during calls. Assign soundboards to queues and agents can trigger clips using keyboard shortcuts.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Soundboards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($soundboards ?? [] as $soundboard)
                    <div class="card hover:shadow-lg transition-shadow">
                        <div class="card-body">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-purple-100 dark:bg-purple-900 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ $soundboard->name }}</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $soundboard->clips_count ?? 0 }} clips</p>
                                    </div>
                                </div>
                                @if($soundboard->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </div>

                            @if($soundboard->description)
                                <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">{{ Str::limit($soundboard->description, 100) }}</p>
                            @endif

                            <!-- Clips Preview -->
                            @if(($soundboard->clips ?? collect())->count() > 0)
                                <div class="mt-4 grid grid-cols-3 gap-2">
                                    @foreach($soundboard->clips->take(6) as $clip)
                                        <div class="p-2 bg-gray-100 dark:bg-gray-800 rounded text-center">
                                            <span class="text-xs font-mono text-gray-500">{{ $clip->shortcut_key ?? '-' }}</span>
                                            <p class="text-xs text-gray-700 dark:text-gray-300 truncate">{{ $clip->name }}</p>
                                        </div>
                                    @endforeach
                                </div>
                                @if($soundboard->clips->count() > 6)
                                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center mt-2">
                                        +{{ $soundboard->clips->count() - 6 }} more clips
                                    </p>
                                @endif
                            @endif

                            <!-- Assigned Queues -->
                            @if(($soundboard->queues ?? collect())->count() > 0)
                                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Assigned to Queues:</p>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($soundboard->queues->take(3) as $queue)
                                            <span class="badge badge-info text-xs">{{ $queue->name }}</span>
                                        @endforeach
                                        @if($soundboard->queues->count() > 3)
                                            <span class="badge badge-gray text-xs">+{{ $soundboard->queues->count() - 3 }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                <a href="{{ route('soundboards.show', $soundboard) }}" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 text-sm font-medium">
                                    Manage Clips
                                </a>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('soundboards.edit', $soundboard) }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('soundboards.destroy', $soundboard) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Delete this soundboard and all its clips?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full">
                        <div class="card">
                            <div class="card-body text-center py-12">
                                <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Soundboards</h3>
                                <p class="text-gray-500 dark:text-gray-400 mb-4">Create a soundboard to let agents play audio clips during calls.</p>
                                <a href="{{ route('soundboards.create') }}" class="btn-primary">
                                    Create Soundboard
                                </a>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>

