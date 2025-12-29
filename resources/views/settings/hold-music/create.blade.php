<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('hold-music.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Create Hold Music Class
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <form action="{{ route('hold-music.store') }}" method="POST">
                @csrf
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-lg font-medium">Hold Music Details</h3>
                    </div>
                    <div class="card-body space-y-6">
                        <div>
                            <label for="name" class="form-label">Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" 
                                   class="form-input @error('name') border-red-500 @enderror" 
                                   placeholder="e.g., Jazz Collection" required>
                            @error('name')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="directory_name" class="form-label">Directory Name <span class="text-red-500">*</span></label>
                            <input type="text" name="directory_name" id="directory_name" value="{{ old('directory_name') }}" 
                                   class="form-input @error('directory_name') border-red-500 @enderror" 
                                   placeholder="e.g., jazz-collection" required
                                   pattern="[a-z0-9-]+" title="Only lowercase letters, numbers, and hyphens">
                            <p class="mt-1 text-sm text-gray-500">Used for Asterisk configuration. Only lowercase letters, numbers, and hyphens.</p>
                            @error('directory_name')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" rows="3" 
                                      class="form-input" placeholder="Optional description for this hold music class">{{ old('description') }}</textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="form-label">Set as Default</label>
                                <div class="mt-2">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        <span class="ml-2 text-gray-700 dark:text-gray-300">Make this the default hold music</span>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label for="is_active" class="form-label">Status</label>
                                <select name="is_active" id="is_active" class="form-select">
                                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer flex items-center justify-end gap-4">
                        <a href="{{ route('hold-music.index') }}" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">Create Hold Music Class</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>







