<x-app-layout>
    @section('title', 'Voicemail')
    @section('page-title', 'Voicemail')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Voicemail</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage voicemail messages
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="badge badge-primary">{{ $unreadCount ?? 0 }} Unread</span>
            </div>
        </div>
    </x-slot>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
        <div class="p-4">
            <form method="GET" class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Search by caller ID..."
                           class="form-input">
                </div>
                <div class="w-40">
                    <select name="status" class="form-select">
                        <option value="">All Messages</option>
                        <option value="unread" {{ request('status') === 'unread' ? 'selected' : '' }}>Unread</option>
                        <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>Read</option>
                    </select>
                </div>
                <button type="submit" class="btn-secondary">Filter</button>
            </form>
        </div>
    </div>

    <!-- Voicemail List -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($voicemails ?? [] as $voicemail)
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors {{ !$voicemail->is_read ? 'bg-accent-50 dark:bg-accent-900/10' : '' }}">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white {{ !$voicemail->is_read ? 'font-bold' : '' }}">
                                    {{ $voicemail->caller_id }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $voicemail->created_at->format('M d, Y H:i') }} · {{ gmdate('i:s', $voicemail->duration) }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            @if(!$voicemail->is_read)
                                <span class="w-2 h-2 bg-accent-500 rounded-full"></span>
                            @endif
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('voicemails.show', $voicemail) }}" 
                                   class="p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600"
                                   title="Play">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('voicemails.download', $voicemail) }}" 
                                   class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600"
                                   title="Download">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                </a>
                                <form action="{{ route('voicemails.destroy', $voicemail) }}" method="POST" class="inline" 
                                      onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600"
                                            title="Delete">
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
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No voicemails</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No voicemail messages in your inbox.</p>
                </div>
            @endforelse
        </div>

        @if(isset($voicemails) && $voicemails->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $voicemails->withQueryString()->links() }}
            </div>
        @endif
    </div>
</x-app-layout>







