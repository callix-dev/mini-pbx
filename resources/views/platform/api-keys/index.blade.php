<x-app-layout>
    @section('title', 'API Keys')
    @section('page-title', 'API Keys')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">API Keys</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage API keys for external integrations
                </p>
            </div>
            <a href="{{ route('api-keys.create') }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                New API Key
            </a>
        </div>
    </x-slot>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Key</th>
                        <th>Permissions</th>
                        <th>Last Used</th>
                        <th>Expires</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($apiKeys ?? [] as $key)
                        <tr>
                            <td>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $key->name }}</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $key->description }}</p>
                            </td>
                            <td>
                                <div class="flex items-center space-x-2">
                                    <code class="text-sm font-mono text-gray-600 dark:text-gray-400">
                                        {{ Str::limit($key->key, 20) }}***
                                    </code>
                                    <button type="button" onclick="copyToClipboard('{{ $key->key }}')" 
                                            class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Copy">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @if($key->permissions && count($key->permissions) > 0)
                                        @php
                                            $readCount = collect($key->permissions)->filter(fn($p) => str_starts_with($p, 'read:'))->count();
                                            $writeCount = collect($key->permissions)->filter(fn($p) => str_starts_with($p, 'write:') || str_starts_with($p, 'delete:'))->count();
                                        @endphp
                                        @if($readCount > 0)
                                            <span class="badge badge-info text-xs">{{ $readCount }} Read</span>
                                        @endif
                                        @if($writeCount > 0)
                                            <span class="badge badge-primary text-xs">{{ $writeCount }} Write</span>
                                        @endif
                                    @else
                                        <span class="badge badge-warning text-xs">Full Access</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{ $key->last_used_at ? $key->last_used_at->diffForHumans() : 'Never' }}
                                </span>
                            </td>
                            <td>
                                @if($key->expires_at)
                                    <span class="{{ $key->expires_at->isPast() ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
                                        {{ $key->expires_at->format('M d, Y') }}
                                    </span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">Never</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $key->is_active ? 'badge-success' : 'badge-gray' }}">
                                    {{ $key->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('api-keys.show', $key) }}" 
                                       class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                       title="View Details">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('api-keys.toggle-status', $key) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" 
                                                class="p-2 text-gray-400 hover:text-yellow-600 dark:hover:text-yellow-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                                title="{{ $key->is_active ? 'Disable' : 'Enable' }}">
                                            @if($key->is_active)
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            @endif
                                        </button>
                                    </form>
                                    <form action="{{ route('api-keys.regenerate', $key) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Regenerating the key will invalidate the current key. Continue?')">
                                        @csrf
                                        <button type="submit" 
                                                class="p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                                title="Regenerate">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                        </button>
                                    </form>
                                    <form action="{{ route('api-keys.destroy', $key) }}" method="POST" class="inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this API key?')">
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
                            <td colspan="7" class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No API keys</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Create an API key for external integrations.</p>
                                <div class="mt-6">
                                    <a href="{{ route('api-keys.create') }}" class="btn-primary">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                        New API Key
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('API key copied to clipboard');
            });
        }
    </script>
    @endpush
</x-app-layout>

