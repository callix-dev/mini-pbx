<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('soundboards.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ $soundboard->name ?? 'Soundboard' }}
                </h2>
                @if($soundboard->is_active ?? false)
                    <span class="badge badge-success">Active</span>
                @else
                    <span class="badge badge-danger">Inactive</span>
                @endif
            </div>
            <a href="{{ route('soundboards.edit', $soundboard ?? 0) }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            <!-- Info & Upload Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Info Card -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-lg font-medium">Details</h3>
                    </div>
                    <div class="card-body space-y-4">
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Total Clips</dt>
                            <dd class="text-2xl font-bold text-gray-900 dark:text-white">{{ ($soundboard->clips ?? collect())->count() }}</dd>
                        </div>
                        @if($soundboard->description ?? null)
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Description</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $soundboard->description }}</dd>
                            </div>
                        @endif
                        @if(($soundboard->queues ?? collect())->count() > 0)
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400 mb-2">Assigned Queues</dt>
                                <dd class="flex flex-wrap gap-1">
                                    @foreach($soundboard->queues as $queue)
                                        <span class="badge badge-info">{{ $queue->name }}</span>
                                    @endforeach
                                </dd>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Upload Form -->
                <div class="lg:col-span-2 card">
                    <div class="card-header">
                        <h3 class="text-lg font-medium">Add New Clip</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('soundboards.upload-clip', $soundboard ?? 0) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="name" class="form-label">Clip Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="name" id="name" class="form-input" placeholder="e.g., Thank You" required>
                                </div>
                                <div>
                                    <label for="shortcut_key" class="form-label">Shortcut Key</label>
                                    <input type="text" name="shortcut_key" id="shortcut_key" class="form-input" placeholder="e.g., F1, 1, A" maxlength="10">
                                    <p class="mt-1 text-xs text-gray-500">Key agents press to play this clip</p>
                                </div>
                            </div>

                            <div>
                                <label for="audio_file" class="form-label">Audio File <span class="text-red-500">*</span></label>
                                <input type="file" name="audio_file" id="audio_file" accept="audio/*,.wav,.mp3,.ogg" 
                                       class="form-input" required>
                                <p class="mt-1 text-xs text-gray-500">WAV, MP3, or OGG format recommended</p>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="btn-primary">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Add Clip
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Clips Grid -->
            <div class="card">
                <div class="card-header">
                    <h3 class="text-lg font-medium">Audio Clips</h3>
                </div>
                <div class="card-body">
                    @if(($soundboard->clips ?? collect())->count() > 0)
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            @foreach($soundboard->clips as $clip)
                                <div class="relative p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary-500 transition-colors group">
                                    <!-- Shortcut Badge -->
                                    @if($clip->shortcut_key)
                                        <div class="absolute -top-2 -right-2 px-2 py-1 bg-purple-600 text-white text-xs font-bold rounded">
                                            {{ $clip->shortcut_key }}
                                        </div>
                                    @endif

                                    <!-- Play Button -->
                                    <button type="button" class="play-clip-btn w-full mb-3" data-src="{{ asset('storage/' . $clip->file_path) }}">
                                        <div class="w-16 h-16 mx-auto rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center hover:bg-primary-200 dark:hover:bg-primary-800 transition-colors">
                                            <svg class="w-8 h-8 text-primary-600 dark:text-primary-400 play-icon" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z"/>
                                            </svg>
                                            <svg class="w-8 h-8 text-primary-600 dark:text-primary-400 pause-icon hidden" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
                                            </svg>
                                        </div>
                                    </button>

                                    <!-- Clip Info -->
                                    <div class="text-center">
                                        <h4 class="font-medium text-gray-900 dark:text-white truncate">{{ $clip->name }}</h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ gmdate('i:s', $clip->duration ?? 0) }}</p>
                                    </div>

                                    <!-- Actions -->
                                    <div class="mt-3 flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <form action="{{ route('soundboards.delete-clip', [$soundboard ?? 0, $clip->id]) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Delete this clip?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1 text-red-600 hover:text-red-800 dark:text-red-400">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Clips Yet</h3>
                            <p class="text-gray-500 dark:text-gray-400">Use the form above to add audio clips to this soundboard.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Audio Player -->
    <audio id="clip-player" class="hidden"></audio>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const clipPlayer = document.getElementById('clip-player');
            let currentBtn = null;

            document.querySelectorAll('.play-clip-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const src = this.dataset.src;
                    const playIcon = this.querySelector('.play-icon');
                    const pauseIcon = this.querySelector('.pause-icon');

                    if (currentBtn && currentBtn !== this) {
                        currentBtn.querySelector('.play-icon').classList.remove('hidden');
                        currentBtn.querySelector('.pause-icon').classList.add('hidden');
                    }

                    if (clipPlayer.src.includes(src.split('/').pop()) && !clipPlayer.paused) {
                        clipPlayer.pause();
                        playIcon.classList.remove('hidden');
                        pauseIcon.classList.add('hidden');
                    } else {
                        clipPlayer.src = src;
                        clipPlayer.play();
                        playIcon.classList.add('hidden');
                        pauseIcon.classList.remove('hidden');
                        currentBtn = this;
                    }
                });
            });

            clipPlayer.addEventListener('ended', function() {
                if (currentBtn) {
                    currentBtn.querySelector('.play-icon').classList.remove('hidden');
                    currentBtn.querySelector('.pause-icon').classList.add('hidden');
                }
            });
        });
    </script>
</x-app-layout>

