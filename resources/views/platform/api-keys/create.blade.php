<x-app-layout>
    @section('title', 'Create API Key')
    @section('page-title', 'Create API Key')

    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('api-keys.index') }}" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Create API Key</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Generate a new API key for external integrations</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <form action="{{ route('api-keys.store') }}" method="POST" class="space-y-4">
            @csrf

            @if ($errors->any())
                <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                    <div class="flex">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <ul class="text-sm text-red-700 dark:text-red-300 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Basic Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white text-sm">Basic Information</h3>
                </div>
                <div class="p-4 space-y-4">
                    <div>
                        <label for="name" class="form-label">Key Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" 
                               class="form-input @error('name') border-red-500 @enderror" 
                               placeholder="e.g., CRM Integration, Mobile App" required>
                        <p class="mt-1 text-xs text-gray-500">A descriptive name to identify this API key</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="rate_limit" class="form-label">Rate Limit (requests/minute)</label>
                            <input type="number" name="rate_limit" id="rate_limit" value="{{ old('rate_limit', 60) }}" 
                                   class="form-input" min="1" max="1000">
                            <p class="mt-1 text-xs text-gray-500">Max requests per minute (1-1000)</p>
                        </div>
                        <div>
                            <label for="expires_at" class="form-label">Expiration Date</label>
                            <input type="date" name="expires_at" id="expires_at" value="{{ old('expires_at') }}" 
                                   class="form-input" min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                            <p class="mt-1 text-xs text-gray-500">Leave empty for no expiration</p>
                        </div>
                    </div>

                    <div>
                        <label for="ip_whitelist" class="form-label">IP Whitelist</label>
                        <input type="text" name="ip_whitelist" id="ip_whitelist" value="{{ old('ip_whitelist') }}" 
                               class="form-input" placeholder="192.168.1.1, 10.0.0.0/24">
                        <p class="mt-1 text-xs text-gray-500">Comma-separated IPs or CIDR ranges. Leave empty to allow all IPs.</p>
                    </div>
                </div>
            </div>

            <!-- Permissions -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white text-sm">Permissions</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Select the API permissions for this key. Leave all unchecked for full access.</p>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Extensions -->
                        <div class="space-y-2">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                Extensions
                            </h4>
                            <label class="flex items-center text-sm">
                                <input type="checkbox" name="permissions[]" value="read:extensions" 
                                       {{ in_array('read:extensions', old('permissions', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-2">
                                <span class="text-gray-700 dark:text-gray-300">Read extensions</span>
                            </label>
                            <label class="flex items-center text-sm">
                                <input type="checkbox" name="permissions[]" value="write:extensions"
                                       {{ in_array('write:extensions', old('permissions', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-2">
                                <span class="text-gray-700 dark:text-gray-300">Manage extensions</span>
                            </label>
                        </div>

                        <!-- Calls -->
                        <div class="space-y-2">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                Calls
                            </h4>
                            <label class="flex items-center text-sm">
                                <input type="checkbox" name="permissions[]" value="read:calls"
                                       {{ in_array('read:calls', old('permissions', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-2">
                                <span class="text-gray-700 dark:text-gray-300">Read call logs</span>
                            </label>
                            <label class="flex items-center text-sm">
                                <input type="checkbox" name="permissions[]" value="write:calls"
                                       {{ in_array('write:calls', old('permissions', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-2">
                                <span class="text-gray-700 dark:text-gray-300">Initiate calls</span>
                            </label>
                        </div>

                        <!-- Queues -->
                        <div class="space-y-2">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Queues
                            </h4>
                            <label class="flex items-center text-sm">
                                <input type="checkbox" name="permissions[]" value="read:queues"
                                       {{ in_array('read:queues', old('permissions', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-2">
                                <span class="text-gray-700 dark:text-gray-300">Read queues</span>
                            </label>
                            <label class="flex items-center text-sm">
                                <input type="checkbox" name="permissions[]" value="write:queues"
                                       {{ in_array('write:queues', old('permissions', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-2">
                                <span class="text-gray-700 dark:text-gray-300">Manage queues</span>
                            </label>
                        </div>

                        <!-- Users -->
                        <div class="space-y-2">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                Users
                            </h4>
                            <label class="flex items-center text-sm">
                                <input type="checkbox" name="permissions[]" value="read:users"
                                       {{ in_array('read:users', old('permissions', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-2">
                                <span class="text-gray-700 dark:text-gray-300">Read users</span>
                            </label>
                            <label class="flex items-center text-sm">
                                <input type="checkbox" name="permissions[]" value="write:users"
                                       {{ in_array('write:users', old('permissions', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-2">
                                <span class="text-gray-700 dark:text-gray-300">Manage users</span>
                            </label>
                        </div>

                        <!-- DIDs -->
                        <div class="space-y-2">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                </svg>
                                DIDs
                            </h4>
                            <label class="flex items-center text-sm">
                                <input type="checkbox" name="permissions[]" value="read:dids"
                                       {{ in_array('read:dids', old('permissions', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-2">
                                <span class="text-gray-700 dark:text-gray-300">Read DIDs</span>
                            </label>
                            <label class="flex items-center text-sm">
                                <input type="checkbox" name="permissions[]" value="write:dids"
                                       {{ in_array('write:dids', old('permissions', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-2">
                                <span class="text-gray-700 dark:text-gray-300">Manage DIDs</span>
                            </label>
                        </div>

                        <!-- Recordings -->
                        <div class="space-y-2">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                </svg>
                                Recordings
                            </h4>
                            <label class="flex items-center text-sm">
                                <input type="checkbox" name="permissions[]" value="read:recordings"
                                       {{ in_array('read:recordings', old('permissions', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-2">
                                <span class="text-gray-700 dark:text-gray-300">Access recordings</span>
                            </label>
                            <label class="flex items-center text-sm">
                                <input type="checkbox" name="permissions[]" value="delete:recordings"
                                       {{ in_array('delete:recordings', old('permissions', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-2">
                                <span class="text-gray-700 dark:text-gray-300">Delete recordings</span>
                            </label>
                        </div>

                        <!-- Webhooks -->
                        <div class="space-y-2">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                                Webhooks
                            </h4>
                            <label class="flex items-center text-sm">
                                <input type="checkbox" name="permissions[]" value="read:webhooks"
                                       {{ in_array('read:webhooks', old('permissions', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-2">
                                <span class="text-gray-700 dark:text-gray-300">Read webhooks</span>
                            </label>
                            <label class="flex items-center text-sm">
                                <input type="checkbox" name="permissions[]" value="write:webhooks"
                                       {{ in_array('write:webhooks', old('permissions', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-2">
                                <span class="text-gray-700 dark:text-gray-300">Manage webhooks</span>
                            </label>
                        </div>

                        <!-- Reports -->
                        <div class="space-y-2">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                Reports
                            </h4>
                            <label class="flex items-center text-sm">
                                <input type="checkbox" name="permissions[]" value="read:reports"
                                       {{ in_array('read:reports', old('permissions', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-2">
                                <span class="text-gray-700 dark:text-gray-300">Access reports</span>
                            </label>
                        </div>
                    </div>

                    <!-- Quick select all -->
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex items-center space-x-4">
                        <button type="button" onclick="selectAll()" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                            Select All
                        </button>
                        <button type="button" onclick="selectNone()" class="text-sm text-gray-600 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            Deselect All
                        </button>
                        <button type="button" onclick="selectReadOnly()" class="text-sm text-gray-600 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            Read Only
                        </button>
                    </div>
                </div>
            </div>

            <!-- Security Notice -->
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800 p-4">
                <div class="flex">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Security Notice</h4>
                        <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                            The API secret will only be shown <strong>once</strong> after creation. Make sure to copy and store it securely.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('api-keys.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    Generate API Key
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function selectAll() {
            document.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = true);
        }
        
        function selectNone() {
            document.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = false);
        }
        
        function selectReadOnly() {
            document.querySelectorAll('input[name="permissions[]"]').forEach(cb => {
                cb.checked = cb.value.startsWith('read:');
            });
        }
    </script>
    @endpush
</x-app-layout>



