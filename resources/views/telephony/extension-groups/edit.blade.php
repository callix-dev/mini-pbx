<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('extension-groups.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Edit Extension Group: {{ $extensionGroup->name }}
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <form action="{{ route('extension-groups.update', $extensionGroup) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="card">
                    <div class="card-header">
                        <h3 class="text-lg font-medium">Group Details</h3>
                    </div>
                    <div class="card-body space-y-6">
                        <div>
                            <label for="name" class="form-label">Group Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="name" 
                                   value="{{ old('name', $extensionGroup->name) }}" 
                                   class="form-input @error('name') border-red-500 @enderror" 
                                   placeholder="e.g., Sales Team" required>
                            @error('name')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" rows="3" 
                                      class="form-input @error('description') border-red-500 @enderror" 
                                      placeholder="Optional description for this group">{{ old('description', $extensionGroup->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="ring_strategy" class="form-label">Ring Strategy <span class="text-red-500">*</span></label>
                                <select name="ring_strategy" id="ring_strategy" class="form-select" required>
                                    @foreach($ringStrategies ?? \App\Models\ExtensionGroup::RING_STRATEGIES as $value => $label)
                                        <option value="{{ $value }}" {{ old('ring_strategy', $extensionGroup->ring_strategy) === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    How calls should be distributed to group members
                                </p>
                            </div>

                            <div>
                                <label for="ring_time" class="form-label">Ring Timeout (seconds)</label>
                                <input type="number" name="ring_time" id="ring_time" 
                                       value="{{ old('ring_time', $extensionGroup->ring_time ?? 30) }}" 
                                       class="form-input" min="5" max="300" required>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    How long to ring before moving to next action
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="is_active" value="1" 
                                   {{ old('is_active', $extensionGroup->is_active) ? 'checked' : '' }}
                                   class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                            <label for="is_active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                Active
                            </label>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="text-lg font-medium">Group Members</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Select extensions to include in this group.
                        </p>
                        @php
                            $selectedExtensions = old('extensions', $extensionGroup->extensions->pluck('id')->toArray());
                        @endphp
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-64 overflow-y-auto">
                            @forelse($extensions ?? [] as $extension)
                                <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                    <input type="checkbox" name="extensions[]" value="{{ $extension->id }}" 
                                           {{ in_array($extension->id, $selectedExtensions) ? 'checked' : '' }}
                                           class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                                    <span class="ml-3">
                                        <span class="block text-sm font-medium text-gray-900 dark:text-white">{{ $extension->extension }}</span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $extension->name }}</span>
                                    </span>
                                </label>
                            @empty
                                <p class="col-span-full text-center text-gray-500 dark:text-gray-400 py-4">
                                    No extensions available.
                                </p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('extension-groups.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Update Group
                    </button>
                </div>
            </form>

            <!-- Delete Form (separate from the update form) -->
            <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-lg font-medium text-red-600 dark:text-red-400">Danger Zone</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Permanently delete this extension group.</p>
                    </div>
                    <form action="{{ route('extension-groups.destroy', $extensionGroup) }}" method="POST" 
                          onsubmit="return confirm('Are you sure you want to delete this group? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Delete Group
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
