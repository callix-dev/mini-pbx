<x-app-layout>
    @section('title', 'Call Details')
    @section('page-title', 'Call Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="{{ route('call-logs.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Call Details</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $callLog->created_at->format('M d, Y H:i:s') }}</p>
                </div>
            </div>
            @if($callLog->recording_path)
                <a href="{{ route('call-logs.download-recording', $callLog) }}" class="btn-secondary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download Recording
                </a>
            @endif
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Call Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Call Information</h3>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Call Type</dt>
                            <dd class="mt-1">
                                @if($callLog->type === 'inbound')
                                    <span class="badge badge-success">Inbound</span>
                                @elseif($callLog->type === 'outbound')
                                    <span class="badge badge-primary">Outbound</span>
                                @else
                                    <span class="badge badge-gray">Internal</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                            <dd class="mt-1">
                                @php
                                    $statusColors = [
                                        'answered' => 'badge-success',
                                        'missed' => 'badge-danger',
                                        'busy' => 'badge-warning',
                                        'failed' => 'badge-gray',
                                    ];
                                @endphp
                                <span class="badge {{ $statusColors[$callLog->status] ?? 'badge-gray' }}">
                                    {{ ucfirst($callLog->status) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">From</dt>
                            <dd class="mt-1 text-lg font-mono font-bold text-gray-900 dark:text-white">{{ $callLog->source }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">To</dt>
                            <dd class="mt-1 text-lg font-mono font-bold text-gray-900 dark:text-white">{{ $callLog->destination }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Duration</dt>
                            <dd class="mt-1 text-lg font-mono text-gray-900 dark:text-white">{{ gmdate('H:i:s', $callLog->duration) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Agent</dt>
                            <dd class="mt-1 text-gray-900 dark:text-white">{{ $callLog->agent->name ?? 'N/A' }}</dd>
                        </div>
                        @if($callLog->queue)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Queue</dt>
                                <dd class="mt-1 text-gray-900 dark:text-white">{{ $callLog->queue->name }}</dd>
                            </div>
                        @endif
                        @if($callLog->did)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">DID</dt>
                                <dd class="mt-1 text-gray-900 dark:text-white">{{ $callLog->did->did_number }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Recording Player -->
            @if($callLog->recording_path)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Call Recording</h3>
                    </div>
                    <div class="p-6">
                        <audio controls class="w-full">
                            <source src="{{ route('call-logs.play-recording', $callLog) }}" type="audio/wav">
                            Your browser does not support the audio element.
                        </audio>
                    </div>
                </div>
            @endif

            <!-- Call Notes -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Call Notes</h3>
                    <span class="badge badge-gray">{{ $callLog->callNotes?->count() ?? 0 }} notes</span>
                </div>
                <div class="p-6">
                    <!-- Add Note Form -->
                    <form action="{{ route('call-logs.add-note', $callLog) }}" method="POST" class="mb-6">
                        @csrf
                        <textarea name="note" rows="3" class="form-input mb-3" placeholder="Add a note..."></textarea>
                        <button type="submit" class="btn-primary">Add Note</button>
                    </form>

                    <!-- Notes List -->
                    @if($callLog->callNotes && $callLog->callNotes->count() > 0)
                        <div class="space-y-4">
                            @foreach($callLog->callNotes as $note)
                                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $note->note }}</p>
                                    <div class="mt-2 flex items-center text-xs text-gray-500 dark:text-gray-400">
                                        <span>{{ $note->user->name ?? 'System' }}</span>
                                        <span class="mx-2">·</span>
                                        <span>{{ $note->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-gray-500 dark:text-gray-400">No notes yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Disposition -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Disposition</h3>
                </div>
                <div class="p-6">
                    <form action="{{ route('call-logs.update-disposition', $callLog) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <select name="disposition_id" class="form-select mb-3">
                            <option value="">-- Select Disposition --</option>
                            @foreach($dispositions ?? [] as $disposition)
                                <option value="{{ $disposition->id }}" {{ $callLog->disposition_id == $disposition->id ? 'selected' : '' }}>
                                    {{ $disposition->name }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn-secondary w-full">Update</button>
                    </form>
                </div>
            </div>

            <!-- Timeline -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Call Timeline</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Call Started</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $callLog->created_at->format('H:i:s') }}</p>
                            </div>
                        </div>

                        @if($callLog->answered_at)
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Answered</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $callLog->answered_at->format('H:i:s') }}</p>
                                </div>
                            </div>
                        @endif

                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Call Ended</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $callLog->ended_at ? $callLog->ended_at->format('H:i:s') : $callLog->created_at->addSeconds($callLog->duration)->format('H:i:s') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Actions</h3>
                </div>
                <div class="p-4 space-y-2">
                    <button type="button" class="w-full btn-secondary text-left">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        Call Back
                    </button>
                    <a href="{{ route('callbacks.store') }}?phone={{ $callLog->source }}" class="w-full btn-secondary text-left flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Schedule Callback
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

