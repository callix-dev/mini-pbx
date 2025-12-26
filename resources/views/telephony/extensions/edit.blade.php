<x-app-layout>
    @section('title', 'Edit Extension')
    @section('page-title', 'Edit Extension')

    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('extensions.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Extension {{ $extension->extension }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Update extension settings</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <form action="{{ route('extensions.update', $extension) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Extension Details</h3>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="extension" class="form-label">Extension Number <span class="text-red-500">*</span></label>
                            <input type="text" name="extension" id="extension" value="{{ old('extension', $extension->extension) }}" 
                                   class="form-input @error('extension') border-red-500 @enderror" 
                                   placeholder="e.g., 1001" required>
                            @error('extension')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="name" class="form-label">Agent Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $extension->name) }}" 
                                   class="form-input @error('name') border-red-500 @enderror" 
                                   placeholder="e.g., John Smith" required>
                            @error('name')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password" 
                                   class="form-input @error('password') border-red-500 @enderror" 
                                   placeholder="Leave blank to keep current">
                            @error('password')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Leave blank to keep the current password</p>
                        </div>

                        <div>
                            <label for="user_id" class="form-label">Assign to User</label>
                            <select name="user_id" id="user_id" class="form-select">
                                <option value="">-- Unassigned --</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id', $extension->user_id) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Voicemail Settings</h3>
                </div>
                <div class="p-6 space-y-6">
                    <div class="flex items-center">
                        <input type="checkbox" name="voicemail_enabled" id="voicemail_enabled" value="1" 
                               {{ old('voicemail_enabled', $extension->voicemail_enabled) ? 'checked' : '' }}
                               class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                        <label for="voicemail_enabled" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                            Enable Voicemail
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="voicemail_password" class="form-label">Voicemail PIN</label>
                            <input type="text" name="voicemail_password" id="voicemail_password" 
                                   value="{{ old('voicemail_password', $extension->voicemail_password) }}" 
                                   class="form-input" placeholder="e.g., 1234">
                        </div>

                        <div>
                            <label for="voicemail_email" class="form-label">Voicemail Email</label>
                            <input type="email" name="voicemail_email" id="voicemail_email" 
                                   value="{{ old('voicemail_email', $extension->voicemail_email) }}" 
                                   class="form-input" placeholder="notifications@example.com">
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Caller ID Settings</h3>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="caller_id_name" class="form-label">Caller ID Name</label>
                            <input type="text" name="caller_id_name" id="caller_id_name" 
                                   value="{{ old('caller_id_name', $extension->caller_id_name) }}" 
                                   class="form-input" placeholder="Display Name">
                        </div>

                        <div>
                            <label for="caller_id_number" class="form-label">Caller ID Number</label>
                            <input type="text" name="caller_id_number" id="caller_id_number" 
                                   value="{{ old('caller_id_number', $extension->caller_id_number) }}" 
                                   class="form-input" placeholder="e.g., +15551234567">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <button type="button" onclick="confirmDelete()" class="btn-danger">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete Extension
                </button>
                
                <div class="flex items-center space-x-3">
                    <a href="{{ route('extensions.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Update Extension
                    </button>
                </div>
            </div>
        </form>

        <form id="delete-form" action="{{ route('extensions.destroy', $extension) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>

    @push('scripts')
    <script>
        function confirmDelete() {
            if (confirm('Are you sure you want to delete this extension? This action cannot be undone.')) {
                document.getElementById('delete-form').submit();
            }
        }
    </script>
    @endpush
</x-app-layout>


