<x-app-layout>
    @section('title', 'API Key: ' . $apiKey->name)
    @section('page-title', 'API Key Details')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="{{ route('api-keys.index') }}" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            {{ $apiKey->name }}
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $apiKey->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                                {{ $apiKey->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            @if($apiKey->isExpired())
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                    Expired
                                </span>
                            @endif
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Created {{ $apiKey->created_at->diffForHumans() }} by {{ $apiKey->user?->name ?? 'Unknown' }}</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <form action="{{ route('api-keys.toggle-status', $apiKey) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="{{ $apiKey->is_active ? 'btn-warning' : 'btn-success' }} text-sm">
                        {{ $apiKey->is_active ? 'Disable' : 'Enable' }}
                    </button>
                </form>
                <form action="{{ route('api-keys.regenerate', $apiKey) }}" method="POST" class="inline"
                      onsubmit="return confirm('This will invalidate the current secret. Continue?')">
                    @csrf
                    <button type="submit" class="btn-secondary text-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Regenerate
                    </button>
                </form>
                <form action="{{ route('api-keys.destroy', $apiKey) }}" method="POST" class="inline"
                      onsubmit="return confirm('Delete this API key? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger text-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <!-- New Secret Alert -->
    @if(session('new_secret'))
        <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex-1">
                    <h4 class="text-sm font-medium text-green-800 dark:text-green-200">New Secret Generated</h4>
                    <p class="mt-1 text-sm text-green-700 dark:text-green-300">Copy this secret now. It will not be shown again.</p>
                    <div class="mt-2 flex items-center space-x-2">
                        <code class="flex-1 px-3 py-2 bg-white dark:bg-gray-800 rounded border border-green-300 dark:border-green-700 text-sm font-mono text-gray-900 dark:text-white" id="new-secret">{{ session('new_secret') }}</code>
                        <button type="button" onclick="copySecret()" class="btn-secondary text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Left Column -->
        <div class="space-y-4">
            <!-- Key Details -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white text-sm">API Credentials</h3>
                </div>
                <div class="p-4 space-y-4">
                    <div>
                        <label class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">API Key</label>
                        <div class="mt-1 flex items-center space-x-2">
                            <code class="flex-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded text-sm font-mono text-gray-900 dark:text-white truncate">{{ $apiKey->key }}</code>
                            <button type="button" onclick="copyToClipboard('{{ $apiKey->key }}')" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">API Secret</label>
                        <div class="mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded text-sm text-gray-500 dark:text-gray-400">
                            ••••••••••••••••••••••••••••••••
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Secret is only shown once at creation or regeneration</p>
                    </div>
                </div>
            </div>

            <!-- Configuration -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white text-sm">Configuration</h3>
                </div>
                <div class="p-4 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Rate Limit</span>
                        <span class="text-gray-900 dark:text-white font-medium">{{ $apiKey->rate_limit ?? 60 }} req/min</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Created</span>
                        <span class="text-gray-900 dark:text-white">{{ $apiKey->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Expires</span>
                        <span class="{{ $apiKey->isExpired() ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                            {{ $apiKey->expires_at ? $apiKey->expires_at->format('M d, Y') : 'Never' }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Last Used</span>
                        <span class="text-gray-900 dark:text-white">{{ $apiKey->last_used_at?->diffForHumans() ?? 'Never' }}</span>
                    </div>
                </div>
            </div>

            <!-- IP Whitelist -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white text-sm">IP Whitelist</h3>
                </div>
                <div class="p-4">
                    @if($apiKey->ip_whitelist && count($apiKey->ip_whitelist) > 0)
                        <div class="space-y-1">
                            @foreach($apiKey->ip_whitelist as $ip)
                                <div class="flex items-center text-sm">
                                    <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <code class="font-mono text-gray-900 dark:text-white">{{ $ip }}</code>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">All IPs allowed</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Permissions -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-white text-sm">Permissions</h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                        {{ $apiKey->permissions ? count($apiKey->permissions) : 'Full Access' }}
                    </span>
                </div>
                <div class="p-4">
                    @if($apiKey->permissions && count($apiKey->permissions) > 0)
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($apiKey->permissions as $permission)
                                @php
                                    $colorClass = 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
                                    if (str_starts_with($permission, 'read:')) {
                                        $colorClass = 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400';
                                    } elseif (str_starts_with($permission, 'write:')) {
                                        $colorClass = 'bg-green-50 text-green-600 dark:bg-green-900/30 dark:text-green-400';
                                    } elseif (str_starts_with($permission, 'delete:')) {
                                        $colorClass = 'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400';
                                    }
                                @endphp
                                <span class="px-2 py-0.5 rounded text-xs font-medium {{ $colorClass }}">
                                    {{ $permission }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <div class="flex items-center text-sm text-yellow-600 dark:text-yellow-400">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            Full access (no restrictions)
                        </div>
                    @endif
                </div>
            </div>

            <!-- Usage Example -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white text-sm">Usage Example</h3>
                </div>
                <div class="p-4">
                    <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                        <pre class="text-sm text-gray-100 font-mono"><code>curl -X GET "{{ url('/api/v1/extensions') }}" \
  -H "X-API-Key: {{ $apiKey->key }}" \
  -H "X-API-Secret: YOUR_SECRET" \
  -H "Content-Type: application/json"</code></pre>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Replace <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">YOUR_SECRET</code> with your API secret.
                    </p>
                </div>
            </div>

            <!-- Recent API Logs -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white text-sm">Recent API Requests</h3>
                </div>
                @if($apiKey->logs && $apiKey->logs->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Time</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Method</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Endpoint</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">IP</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($apiKey->logs as $log)
                                    <tr>
                                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                            {{ $log->created_at->format('M d, H:i:s') }}
                                        </td>
                                        <td class="px-4 py-2">
                                            @php
                                                $methodColors = [
                                                    'GET' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                                    'POST' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                                    'PUT' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                    'PATCH' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                                                    'DELETE' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                                ];
                                            @endphp
                                            <span class="px-1.5 py-0.5 rounded text-xs font-medium {{ $methodColors[$log->method] ?? 'bg-gray-100 text-gray-700' }}">
                                                {{ $log->method }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 font-mono text-xs text-gray-900 dark:text-white">
                                            {{ Str::limit($log->endpoint, 40) }}
                                        </td>
                                        <td class="px-4 py-2">
                                            <span class="px-1.5 py-0.5 rounded text-xs font-medium {{ $log->status_code >= 200 && $log->status_code < 300 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                                                {{ $log->status_code }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 font-mono text-xs text-gray-500 dark:text-gray-400">
                                            {{ $log->ip_address }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-8 text-center">
                        <svg class="w-8 h-8 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400">No API requests yet</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Copied to clipboard!');
            });
        }
        
        function copySecret() {
            const secret = document.getElementById('new-secret').textContent;
            navigator.clipboard.writeText(secret).then(() => {
                alert('Secret copied to clipboard!');
            });
        }
    </script>
    @endpush
</x-app-layout>



