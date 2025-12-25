<x-app-layout>
    @section('title', 'Call Logs')
    @section('page-title', 'Call Logs')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Call Logs</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    View and manage call detail records
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('call-logs.export') }}?{{ http_build_query(request()->all()) }}" class="btn-secondary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export
                </a>
                <a href="{{ route('call-logs.analytics') }}" class="btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Analytics
                </a>
            </div>
        </div>
    </x-slot>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
        <div class="p-4">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <div>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Search phone number..."
                           class="form-input">
                </div>
                <div>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="inbound" {{ request('type') === 'inbound' ? 'selected' : '' }}>Inbound</option>
                        <option value="outbound" {{ request('type') === 'outbound' ? 'selected' : '' }}>Outbound</option>
                        <option value="internal" {{ request('type') === 'internal' ? 'selected' : '' }}>Internal</option>
                    </select>
                </div>
                <div>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="answered" {{ request('status') === 'answered' ? 'selected' : '' }}>Answered</option>
                        <option value="missed" {{ request('status') === 'missed' ? 'selected' : '' }}>Missed</option>
                        <option value="busy" {{ request('status') === 'busy' ? 'selected' : '' }}>Busy</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
                <div>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" 
                           class="form-input" placeholder="From date">
                </div>
                <div>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" 
                           class="form-input" placeholder="To date">
                </div>
                <div class="flex items-center space-x-2">
                    <button type="submit" class="btn-secondary flex-1">Filter</button>
                    @if(request()->hasAny(['search', 'type', 'status', 'date_from', 'date_to']))
                        <a href="{{ route('call-logs.index') }}" class="btn-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Call Logs Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Extension</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th class="text-center">Recording</th>
                        <th>Disposition</th>
                        <th>Date/Time</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($callLogs ?? [] as $call)
                        <tr>
                            <td>
                                @if($call->type === 'inbound')
                                    <span class="flex items-center text-green-600 dark:text-green-400">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        Inbound
                                    </span>
                                @elseif($call->type === 'outbound')
                                    <span class="flex items-center text-blue-600 dark:text-blue-400">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        Outbound
                                    </span>
                                @else
                                    <span class="flex items-center text-gray-600 dark:text-gray-400">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                        </svg>
                                        Internal
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div>
                                    <span class="font-mono text-gray-900 dark:text-white">{{ $call->caller_id }}</span>
                                    @if($call->caller_name && $call->caller_name !== $call->caller_id)
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $call->caller_name }}</p>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span class="font-mono text-gray-900 dark:text-white">{{ $call->callee_id }}</span>
                                    @if($call->callee_name && $call->callee_name !== $call->callee_id)
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $call->callee_name }}</p>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($call->extension)
                                    <span class="text-gray-900 dark:text-white">{{ $call->extension->name ?? $call->extension->extension }}</span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="font-mono text-gray-600 dark:text-gray-400">
                                    {{ gmdate('H:i:s', $call->duration) }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'answered' => 'badge-success',
                                        'missed' => 'badge-danger',
                                        'busy' => 'badge-warning',
                                        'failed' => 'badge-gray',
                                    ];
                                @endphp
                                <span class="badge {{ $statusColors[$call->status] ?? 'badge-gray' }}">
                                    {{ ucfirst($call->status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($call->recording_path)
                                    <button 
                                        type="button"
                                        onclick="playInlineRecording('{{ route('call-logs.play-recording', $call) }}', this)"
                                        class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-full hover:bg-green-200 dark:text-green-400 dark:bg-green-900/30 dark:hover:bg-green-900/50 transition-colors"
                                        title="Click to play">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                        Play
                                    </button>
                                @else
                                    <span class="text-gray-400 dark:text-gray-600">—</span>
                                @endif
                            </td>
                            <td>
                                @if($call->disposition)
                                    <span class="badge badge-primary">{{ $call->disposition->name }}</span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-gray-600 dark:text-gray-400">{{ $call->created_at->format('M d, Y H:i') }}</span>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    @if($call->recording_path)
                                        <a href="{{ route('call-logs.play-recording', $call) }}" 
                                           class="p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                           title="Play Recording">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </a>
                                    @endif
                                    <a href="{{ route('call-logs.show', $call) }}" 
                                       class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                       title="View Details">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No call logs found</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Call records will appear here once calls are made.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($callLogs) && $callLogs->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $callLogs->withQueryString()->links() }}
            </div>
        @endif
    </div>

    <!-- Inline Audio Player (floating) -->
    <div id="inlineAudioPlayer" class="fixed bottom-20 left-1/2 transform -translate-x-1/2 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 p-4 hidden z-50 min-w-[400px]">
        <div class="flex items-center space-x-4">
            <button type="button" onclick="togglePlayPause()" class="flex-shrink-0 w-10 h-10 bg-primary-600 hover:bg-primary-700 text-white rounded-full flex items-center justify-center transition-colors">
                <svg id="playIcon" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z"/>
                </svg>
                <svg id="pauseIcon" class="w-5 h-5 hidden" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M6 4h4v16H6zM14 4h4v16h-4z"/>
                </svg>
            </button>
            <div class="flex-1">
                <div class="flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400 mb-1">
                    <span id="currentTime">0:00</span>
                    <span>/</span>
                    <span id="duration">0:00</span>
                </div>
                <input type="range" id="progressBar" min="0" max="100" value="0" 
                       class="w-full h-1 bg-gray-200 dark:bg-gray-700 rounded-full appearance-none cursor-pointer accent-primary-600"
                       onchange="seekAudio(this.value)">
            </div>
            <button type="button" onclick="closeInlinePlayer()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <audio id="audioElement" class="hidden"></audio>
    </div>

    @push('scripts')
    <script>
        let currentButton = null;
        const audioElement = document.getElementById('audioElement');
        const playerContainer = document.getElementById('inlineAudioPlayer');
        const playIcon = document.getElementById('playIcon');
        const pauseIcon = document.getElementById('pauseIcon');
        const progressBar = document.getElementById('progressBar');
        const currentTimeEl = document.getElementById('currentTime');
        const durationEl = document.getElementById('duration');

        function formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }

        function playInlineRecording(url, button) {
            // Reset previous button
            if (currentButton && currentButton !== button) {
                currentButton.innerHTML = `
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    Play
                `;
            }

            // If same button, toggle play/pause
            if (currentButton === button && !audioElement.paused) {
                audioElement.pause();
                return;
            }

            currentButton = button;
            audioElement.src = url;
            playerContainer.classList.remove('hidden');
            audioElement.play();

            button.innerHTML = `
                <svg class="w-3 h-3 mr-1 animate-pulse" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M6 4h4v16H6zM14 4h4v16h-4z"/>
                </svg>
                Playing
            `;
        }

        function togglePlayPause() {
            if (audioElement.paused) {
                audioElement.play();
            } else {
                audioElement.pause();
            }
        }

        function closeInlinePlayer() {
            audioElement.pause();
            audioElement.src = '';
            playerContainer.classList.add('hidden');
            if (currentButton) {
                currentButton.innerHTML = `
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    Play
                `;
                currentButton = null;
            }
        }

        function seekAudio(value) {
            if (audioElement.duration) {
                audioElement.currentTime = (value / 100) * audioElement.duration;
            }
        }

        audioElement.addEventListener('play', () => {
            playIcon.classList.add('hidden');
            pauseIcon.classList.remove('hidden');
        });

        audioElement.addEventListener('pause', () => {
            playIcon.classList.remove('hidden');
            pauseIcon.classList.add('hidden');
            if (currentButton) {
                currentButton.innerHTML = `
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    Play
                `;
            }
        });

        audioElement.addEventListener('timeupdate', () => {
            if (audioElement.duration) {
                const progress = (audioElement.currentTime / audioElement.duration) * 100;
                progressBar.value = progress;
                currentTimeEl.textContent = formatTime(audioElement.currentTime);
            }
        });

        audioElement.addEventListener('loadedmetadata', () => {
            durationEl.textContent = formatTime(audioElement.duration);
        });

        audioElement.addEventListener('ended', () => {
            progressBar.value = 0;
            currentTimeEl.textContent = '0:00';
            if (currentButton) {
                currentButton.innerHTML = `
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    Play
                `;
            }
        });
    </script>
    @endpush
</x-app-layout>

