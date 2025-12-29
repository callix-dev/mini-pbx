<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Hold Music
            </h2>
            <a href="{{ route('hold-music.create') }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Hold Music Class
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- Info Banner -->
            <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">About Hold Music</h3>
                        <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                            Hold music classes can be assigned to queues. Each class can contain multiple audio files that will be played in sequence or randomly when callers are on hold.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Hold Music Classes Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($holdMusic ?? [] as $music)
                    <div class="card hover:shadow-lg transition-shadow">
                        <div class="card-body">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ $music->name }}</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $music->directory_name }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($music->is_default)
                                        <span class="badge badge-primary">Default</span>
                                    @endif
                                    @if($music->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-danger">Inactive</span>
                                    @endif
                                </div>
                            </div>

                            @if($music->description)
                                <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">{{ Str::limit($music->description, 100) }}</p>
                            @endif

                            <div class="mt-4 flex items-center text-sm text-gray-500 dark:text-gray-400">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                </svg>
                                {{ $music->files_count ?? 0 }} audio files
                            </div>

                            <!-- Audio Files Preview -->
                            @if(($music->files ?? collect())->count() > 0)
                                <div class="mt-4 space-y-2">
                                    @foreach($music->files->take(3) as $file)
                                        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded text-sm">
                                            <span class="truncate text-gray-700 dark:text-gray-300">{{ $file->original_filename }}</span>
                                            <span class="text-gray-500">{{ gmdate('i:s', $file->duration ?? 0) }}</span>
                                        </div>
                                    @endforeach
                                    @if($music->files->count() > 3)
                                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
                                            +{{ $music->files->count() - 3 }} more files
                                        </p>
                                    @endif
                                </div>
                            @endif

                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                <a href="{{ route('hold-music.show', $music) }}" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 text-sm font-medium">
                                    View & Manage Files
                                </a>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('hold-music.edit', $music) }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    @if(!$music->is_default)
                                        <form action="{{ route('hold-music.destroy', $music) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Are you sure you want to delete this hold music class?')">
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
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full">
                        <div class="card">
                            <div class="card-body text-center py-12">
                                <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Hold Music Classes</h3>
                                <p class="text-gray-500 dark:text-gray-400 mb-4">Create your first hold music class to get started.</p>
                                <a href="{{ route('hold-music.create') }}" class="btn-primary">
                                    Create Hold Music Class
                                </a>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>







