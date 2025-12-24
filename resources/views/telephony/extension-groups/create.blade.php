<x-app-layout>
    @section('title', 'Create Extension Group')
    @section('page-title', 'Create Extension Group')

    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('extension-groups.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Create Extension Group</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Create a new group with ring strategy</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <form action="{{ route('extension-groups.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Group Details</h3>
                </div>
                <div class="p-6 space-y-6">
                    <div>
                        <label for="name" class="form-label">Group Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" 
                               class="form-input @error('name') border-red-500 @enderror" 
                               placeholder="e.g., Sales Team" required>
                        @error('name')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" id="description" rows="3" 
                                  class="form-input @error('description') border-red-500 @enderror" 
                                  placeholder="Optional description for this group">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="ring_strategy" class="form-label">Ring Strategy <span class="text-red-500">*</span></label>
                            <select name="ring_strategy" id="ring_strategy" class="form-select" required>
                                @foreach($ringStrategies ?? \App\Models\ExtensionGroup::RING_STRATEGIES as $value => $label)
                                    <option value="{{ $value }}" {{ old('ring_strategy', 'ringall') === $value ? 'selected' : '' }}>
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
                                   value="{{ old('ring_time', 30) }}" 
                                   class="form-input" min="5" max="300" required>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                How long to ring before moving to next action
                            </p>
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

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Group Members</h3>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Select extensions to include in this group. You can also add members after creating the group.
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-64 overflow-y-auto">
                        @foreach($extensions ?? [] as $extension)
                            <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                <input type="checkbox" name="extensions[]" value="{{ $extension->id }}" 
                                       {{ in_array($extension->id, old('extensions', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                                <span class="ml-3">
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">{{ $extension->extension }}</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $extension->name }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @if(empty($extensions) || count($extensions) === 0)
                        <p class="text-center text-gray-500 dark:text-gray-400 py-4">
                            No extensions available. Create extensions first.
                        </p>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('extension-groups.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Create Group
                </button>
            </div>
        </form>
    </div>
</x-app-layout>

