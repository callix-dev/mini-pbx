<x-app-layout>
    @section('title', 'Edit Carrier')
    @section('page-title', 'Edit Carrier')

    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('carriers.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Carrier</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Update carrier configuration</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <!-- Validation Errors -->
        @if($errors->any())
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800">
                <div class="flex">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <h4 class="text-sm font-medium text-red-800 dark:text-red-200">Please fix the following errors:</h4>
                        <ul class="mt-2 text-sm text-red-700 dark:text-red-300 list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('carriers.update', $carrier) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            @if($carrier->provider_slug)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                    <div class="px-6 py-4 flex items-center space-x-4">
                        <img src="{{ asset('images/carriers/' . $carrier->provider_slug . '.svg') }}" 
                             alt="{{ $carrier->provider_name }}" 
                             class="w-12 h-12 object-contain">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $carrier->provider_name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Created from template</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Carrier Details</h3>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="form-label">Carrier Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $carrier->name) }}" 
                                   class="form-input @error('name') border-red-500 @enderror" 
                                   placeholder="e.g., Twilio" required>
                            @error('name')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="type" class="form-label">Carrier Type <span class="text-red-500">*</span></label>
                            <select name="type" id="type" class="form-select @error('type') border-red-500 @enderror" required>
                                <option value="outbound" {{ old('type', $carrier->type) === 'outbound' ? 'selected' : '' }}>Outbound</option>
                                <option value="inbound" {{ old('type', $carrier->type) === 'inbound' ? 'selected' : '' }}>Inbound</option>
                            </select>
                            @error('type')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-2">
                            <label for="host" class="form-label">Host/IP <span class="text-red-500">*</span></label>
                            <input type="text" name="host" id="host" value="{{ old('host', $carrier->host) }}" 
                                   class="form-input @error('host') border-red-500 @enderror" 
                                   placeholder="e.g., sip.carrier.com" required>
                            @error('host')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="port" class="form-label">Port <span class="text-red-500">*</span></label>
                            <input type="number" name="port" id="port" value="{{ old('port', $carrier->port) }}" 
                                   class="form-input @error('port') border-red-500 @enderror" min="1" max="65535" required>
                            @error('port')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="transport" class="form-label">Transport Protocol <span class="text-red-500">*</span></label>
                            <select name="transport" id="transport" class="form-select @error('transport') border-red-500 @enderror" required>
                                <option value="udp" {{ old('transport', $carrier->transport) === 'udp' ? 'selected' : '' }}>UDP</option>
                                <option value="tcp" {{ old('transport', $carrier->transport) === 'tcp' ? 'selected' : '' }}>TCP</option>
                                <option value="tls" {{ old('transport', $carrier->transport) === 'tls' ? 'selected' : '' }}>TLS</option>
                            </select>
                            @error('transport')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="auth_type" class="form-label">Authentication Type <span class="text-red-500">*</span></label>
                            <select name="auth_type" id="auth_type" class="form-select @error('auth_type') border-red-500 @enderror" required>
                                <option value="ip" {{ old('auth_type', $carrier->auth_type) === 'ip' ? 'selected' : '' }}>IP Based</option>
                                <option value="registration" {{ old('auth_type', $carrier->auth_type) === 'registration' ? 'selected' : '' }}>Registration</option>
                            </select>
                            @error('auth_type')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="username" class="form-label">Username</label>
                            <input type="text" name="username" id="username" value="{{ old('username', $carrier->username) }}" 
                                   class="form-input" placeholder="SIP username">
                        </div>

                        <div>
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password" 
                                   class="form-input" placeholder="Leave empty to keep current">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Leave empty to keep current password</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="from_domain" class="form-label">From Domain</label>
                            <input type="text" name="from_domain" id="from_domain" value="{{ old('from_domain', $carrier->from_domain) }}" 
                                   class="form-input" placeholder="e.g., sip.example.com">
                        </div>

                        <div>
                            <label for="from_user" class="form-label">From User</label>
                            <input type="text" name="from_user" id="from_user" value="{{ old('from_user', $carrier->from_user) }}" 
                                   class="form-input" placeholder="e.g., caller">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="context" class="form-label">Asterisk Context <span class="text-red-500">*</span></label>
                            <input type="text" name="context" id="context" value="{{ old('context', $carrier->context) }}" 
                                   class="form-input @error('context') border-red-500 @enderror" 
                                   placeholder="e.g., from-trunk" required>
                            @error('context')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="max_channels" class="form-label">Max Channels</label>
                            <input type="number" name="max_channels" id="max_channels" value="{{ old('max_channels', $carrier->max_channels) }}" 
                                   class="form-input" min="1" placeholder="Unlimited">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Leave empty for unlimited</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="priority" class="form-label">Priority</label>
                            <input type="number" name="priority" id="priority" value="{{ old('priority', $carrier->priority) }}" 
                                   class="form-input" min="0" max="100">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Lower number = higher priority (0-100)</p>
                        </div>

                        <div>
                            <label for="backup_carrier_id" class="form-label">Failover Carrier</label>
                            <select name="backup_carrier_id" id="backup_carrier_id" class="form-select">
                                <option value="">None</option>
                                @foreach($otherCarriers ?? [] as $id => $name)
                                    <option value="{{ $id }}" {{ old('backup_carrier_id', $carrier->backup_carrier_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Route to this carrier if primary fails</p>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1" 
                               {{ old('is_active', $carrier->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                        <label for="is_active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                            Active
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('carriers.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Update Carrier
                </button>
            </div>
        </form>

        <!-- Delete Form (separate) -->
        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-medium text-red-600 dark:text-red-400">Danger Zone</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Permanently delete this carrier</p>
                </div>
                <form action="{{ route('carriers.destroy', $carrier) }}" method="POST" 
                      onsubmit="return confirm('Are you sure you want to delete this carrier? This action cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete Carrier
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

