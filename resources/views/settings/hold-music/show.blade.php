<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('hold-music.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ $holdMusic->name ?? 'Hold Music' }}
                </h2>
                @if($holdMusic->is_default ?? false)
                    <span class="badge badge-primary">Default</span>
                @endif
            </div>
            <a href="{{ route('hold-music.edit', $holdMusic ?? 0) }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <!-- Info Card -->
            <div class="card mb-6">
                <div class="card-body">
                    <dl class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Directory Name</dt>
                            <dd class="mt-1 font-mono text-gray-900 dark:text-white">{{ $holdMusic->directory_name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                            <dd class="mt-1">
                                @if($holdMusic->is_active ?? false)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Files</dt>
                            <dd class="mt-1 text-gray-900 dark:text-white">{{ ($holdMusic->files ?? collect())->count() }} files</dd>
                        </div>
                        @if($holdMusic->description ?? null)
                            <div class="md:col-span-3">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
                                <dd class="mt-1 text-gray-900 dark:text-white">{{ $holdMusic->description }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Upload Form -->
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="text-lg font-medium">Upload Audio Files</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('hold-music.upload-file', $holdMusic) }}" method="POST" enctype="multipart/form-data" 
                          id="upload-form" class="space-y-4">
                        @csrf
                        
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center hover:border-primary-500 transition-colors"
                             id="drop-zone">
                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            <p class="text-gray-600 dark:text-gray-400 mb-2">Drag and drop audio files here, or</p>
                            <label class="btn-primary cursor-pointer">
                                Browse Files
                                <input type="file" name="files[]" multiple accept="audio/*,.wav,.mp3,.ogg,.gsm,.ulaw,.alaw" class="hidden" id="file-input">
                            </label>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                Supported formats: WAV, MP3, OGG, GSM, uLaw, aLaw
                            </p>
                        </div>

                        <div id="file-list" class="hidden space-y-2"></div>

                        <div class="flex justify-end">
                            <button type="submit" id="upload-btn" class="btn-primary hidden">
                                <svg class="w-5 h-5 mr-2 animate-spin hidden" id="upload-spinner" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Upload Files
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Audio Files List -->
            <div class="card">
                <div class="card-header">
                    <h3 class="text-lg font-medium">Audio Files</h3>
                </div>
                <div class="card-body p-0">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-16">#</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">File Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-28">Duration</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-24">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-24">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="sortable-files" class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($holdMusic->files ?? [] as $index => $file)
                                    <tr data-id="{{ $file->id }}" class="cursor-move hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                                            </svg>
                                            {{ $index + 1 }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <button type="button" class="play-btn mr-3 text-primary-600 hover:text-primary-800" data-src="{{ asset('storage/' . $file->file_path) }}">
                                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M8 5v14l11-7z"/>
                                                    </svg>
                                                </button>
                                                <div>
                                                    <p class="font-medium text-gray-900 dark:text-white">{{ $file->original_filename }}</p>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $file->converted_path ? 'Converted' : 'Original' }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ gmdate('i:s', $file->duration ?? 0) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($file->is_active)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <form action="{{ route('hold-music.delete-file', [$holdMusic, $file]) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('Delete this audio file?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                            No audio files uploaded yet. Use the form above to upload files.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Audio Player -->
    <audio id="audio-player" class="hidden"></audio>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('file-input');
            const fileList = document.getElementById('file-list');
            const uploadBtn = document.getElementById('upload-btn');
            const dropZone = document.getElementById('drop-zone');
            const audioPlayer = document.getElementById('audio-player');

            // File selection
            fileInput.addEventListener('change', function() {
                updateFileList(this.files);
            });

            // Drag and drop
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('border-primary-500', 'bg-primary-50', 'dark:bg-primary-900/20');
            });

            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('border-primary-500', 'bg-primary-50', 'dark:bg-primary-900/20');
            });

            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('border-primary-500', 'bg-primary-50', 'dark:bg-primary-900/20');
                fileInput.files = e.dataTransfer.files;
                updateFileList(e.dataTransfer.files);
            });

            function updateFileList(files) {
                if (files.length > 0) {
                    fileList.classList.remove('hidden');
                    uploadBtn.classList.remove('hidden');
                    fileList.innerHTML = '';
                    
                    Array.from(files).forEach(file => {
                        const div = document.createElement('div');
                        div.className = 'flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg';
                        div.innerHTML = `
                            <span class="text-gray-700 dark:text-gray-300">${file.name}</span>
                            <span class="text-gray-500 text-sm">${(file.size / 1024 / 1024).toFixed(2)} MB</span>
                        `;
                        fileList.appendChild(div);
                    });
                }
            }

            // Play buttons
            document.querySelectorAll('.play-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const src = this.dataset.src;
                    if (audioPlayer.src === src && !audioPlayer.paused) {
                        audioPlayer.pause();
                        this.innerHTML = '<svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>';
                    } else {
                        document.querySelectorAll('.play-btn').forEach(b => {
                            b.innerHTML = '<svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>';
                        });
                        audioPlayer.src = src;
                        audioPlayer.play();
                        this.innerHTML = '<svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/></svg>';
                    }
                });
            });

            audioPlayer.addEventListener('ended', function() {
                document.querySelectorAll('.play-btn').forEach(b => {
                    b.innerHTML = '<svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>';
                });
            });
        });
    </script>
</x-app-layout>

