<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('ivrs.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Create IVR Menu
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <form action="{{ route('ivrs.store') }}" method="POST">
                @csrf
                
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="text-lg font-medium">Basic Information</h3>
                    </div>
                    <div class="card-body space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="form-label">Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" 
                                       class="form-input @error('name') border-red-500 @enderror" required>
                                @error('name')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="is_active" class="form-label">Status</label>
                                <select name="is_active" id="is_active" class="form-select">
                                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" rows="2" 
                                      class="form-input">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Greeting Settings -->
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="text-lg font-medium">Greeting</h3>
                    </div>
                    <div class="card-body space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="greeting_file_id" class="form-label">Greeting Audio</label>
                                <select name="greeting_file_id" id="greeting_file_id" class="form-select">
                                    <option value="">Select audio file</option>
                                    @foreach($audioFiles ?? [] as $file)
                                        <option value="{{ $file->id }}">{{ $file->name }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-sm text-gray-500">The audio that plays when callers enter this IVR</p>
                            </div>
                            <div>
                                <label for="timeout" class="form-label">Timeout (seconds)</label>
                                <input type="number" name="timeout" id="timeout" value="{{ old('timeout', 10) }}" 
                                       min="1" max="60" class="form-input">
                                <p class="mt-1 text-sm text-gray-500">Time to wait for input before timeout action</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="invalid_retries" class="form-label">Invalid Input Retries</label>
                                <input type="number" name="invalid_retries" id="invalid_retries" value="{{ old('invalid_retries', 3) }}" 
                                       min="1" max="10" class="form-input">
                            </div>
                            <div>
                                <label for="timeout_retries" class="form-label">Timeout Retries</label>
                                <input type="number" name="timeout_retries" id="timeout_retries" value="{{ old('timeout_retries', 3) }}" 
                                       min="1" max="10" class="form-input">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- IVR Options -->
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="text-lg font-medium">Menu Options</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Configure what happens when callers press each key
                        </p>
                    </div>
                    <div class="card-body">
                        <div class="space-y-4">
                            @foreach(['1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '*', '#'] as $key)
                                <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                        <span class="text-xl font-bold text-primary-600 dark:text-primary-400">{{ $key }}</span>
                                    </div>
                                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <select name="options[{{ $key }}][action]" class="form-select">
                                                <option value="">Disabled</option>
                                                <option value="extension">Go to Extension</option>
                                                <option value="queue">Go to Queue</option>
                                                <option value="ivr">Go to IVR</option>
                                                <option value="ring_tree">Go to Ring Tree</option>
                                                <option value="voicemail">Go to Voicemail</option>
                                                <option value="external">Dial External</option>
                                                <option value="hangup">Hangup</option>
                                                <option value="repeat">Repeat Menu</option>
                                            </select>
                                        </div>
                                        <div>
                                            <input type="text" name="options[{{ $key }}][destination]" 
                                                   class="form-input" placeholder="Destination">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Fallback Actions -->
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="text-lg font-medium">Fallback Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                                <h4 class="font-medium text-amber-800 dark:text-amber-200 mb-4">On Invalid Input</h4>
                                <div class="space-y-4">
                                    <div>
                                        <label class="form-label">Action</label>
                                        <select name="invalid_action" class="form-select">
                                            <option value="repeat">Repeat Menu</option>
                                            <option value="voicemail">Go to Voicemail</option>
                                            <option value="extension">Go to Extension</option>
                                            <option value="hangup">Hangup</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label">Destination</label>
                                        <input type="text" name="invalid_destination" class="form-input" placeholder="e.g., extension number">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                <h4 class="font-medium text-red-800 dark:text-red-200 mb-4">On Timeout</h4>
                                <div class="space-y-4">
                                    <div>
                                        <label class="form-label">Action</label>
                                        <select name="timeout_action" class="form-select">
                                            <option value="repeat">Repeat Menu</option>
                                            <option value="voicemail">Go to Voicemail</option>
                                            <option value="extension">Go to Extension</option>
                                            <option value="hangup">Hangup</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label">Destination</label>
                                        <input type="text" name="timeout_destination" class="form-input" placeholder="e.g., extension number">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('ivrs.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">Create IVR</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

