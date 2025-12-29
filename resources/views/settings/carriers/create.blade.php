<x-app-layout>
    @section('title', 'Create Carrier')
    @section('page-title', 'Create Carrier')

    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('carriers.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Create Carrier</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Configure a new SIP carrier</p>
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

        <form action="{{ route('carriers.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Carrier Details</h3>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="form-label">Carrier Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" 
                                   class="form-input @error('name') border-red-500 @enderror" 
                                   placeholder="e.g., Twilio" required>
                            @error('name')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="type" class="form-label">Carrier Type <span class="text-red-500">*</span></label>
                            <select name="type" id="type" class="form-select @error('type') border-red-500 @enderror" required>
                                <option value="outbound" {{ old('type') === 'outbound' ? 'selected' : '' }}>Outbound</option>
                                <option value="inbound" {{ old('type') === 'inbound' ? 'selected' : '' }}>Inbound</option>
                            </select>
                            @error('type')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-2">
                            <label for="host" class="form-label">Host/IP <span class="text-red-500">*</span></label>
                            <input type="text" name="host" id="host" value="{{ old('host') }}" 
                                   class="form-input @error('host') border-red-500 @enderror" 
                                   placeholder="e.g., sip.carrier.com" required>
                            @error('host')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="port" class="form-label">Port <span class="text-red-500">*</span></label>
                            <input type="number" name="port" id="port" value="{{ old('port', 5060) }}" 
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
                                <option value="udp" {{ old('transport', 'udp') === 'udp' ? 'selected' : '' }}>UDP</option>
                                <option value="tcp" {{ old('transport') === 'tcp' ? 'selected' : '' }}>TCP</option>
                                <option value="tls" {{ old('transport') === 'tls' ? 'selected' : '' }}>TLS</option>
                            </select>
                            @error('transport')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="auth_type" class="form-label">Authentication Type <span class="text-red-500">*</span></label>
                            <select name="auth_type" id="auth_type" class="form-select @error('auth_type') border-red-500 @enderror" required>
                                <option value="ip" {{ old('auth_type', 'ip') === 'ip' ? 'selected' : '' }}>IP Based</option>
                                <option value="registration" {{ old('auth_type') === 'registration' ? 'selected' : '' }}>Registration</option>
                            </select>
                            @error('auth_type')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="username" class="form-label">Username</label>
                            <input type="text" name="username" id="username" value="{{ old('username') }}" 
                                   class="form-input" placeholder="SIP username">
                        </div>

                        <div>
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password" 
                                   class="form-input" placeholder="SIP password">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="from_domain" class="form-label">From Domain</label>
                            <input type="text" name="from_domain" id="from_domain" value="{{ old('from_domain') }}" 
                                   class="form-input" placeholder="e.g., sip.example.com">
                        </div>

                        <div>
                            <label for="from_user" class="form-label">From User</label>
                            <input type="text" name="from_user" id="from_user" value="{{ old('from_user') }}" 
                                   class="form-input" placeholder="e.g., caller">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="context" class="form-label">Asterisk Context <span class="text-red-500">*</span></label>
                            <input type="text" name="context" id="context" value="{{ old('context', 'from-trunk') }}" 
                                   class="form-input @error('context') border-red-500 @enderror" 
                                   placeholder="e.g., from-trunk" required>
                            @error('context')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="max_channels" class="form-label">Max Channels</label>
                            <input type="number" name="max_channels" id="max_channels" value="{{ old('max_channels') }}" 
                                   class="form-input" min="1" placeholder="Unlimited">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Leave empty for unlimited</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="priority" class="form-label">Priority</label>
                            <input type="number" name="priority" id="priority" value="{{ old('priority', 1) }}" 
                                   class="form-input" min="0" max="100">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Lower number = higher priority (0-100)</p>
                        </div>

                        <div>
                            <label for="backup_carrier_id" class="form-label">Failover Carrier</label>
                            <select name="backup_carrier_id" id="backup_carrier_id" class="form-select">
                                <option value="">None</option>
                                @foreach($otherCarriers ?? [] as $id => $name)
                                    <option value="{{ $id }}" {{ old('backup_carrier_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Route to this carrier if primary fails</p>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1" 
                               {{ old('is_active', true) ? 'checked' : '' }}
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
                    Create Carrier
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
