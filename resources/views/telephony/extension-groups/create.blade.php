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

    <div class="max-w-4xl mx-auto">
        <form action="{{ route('extension-groups.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Basic Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Group Details</h3>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                            <label for="group_number" class="form-label">Group Number</label>
                            <div class="flex items-center">
                                <span class="px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-r-0 border-gray-300 dark:border-gray-600 rounded-l-lg text-gray-500 dark:text-gray-400">*6</span>
                                <input type="text" name="group_number" id="group_number" 
                                       value="{{ old('group_number', $suggestedNumber ?? '') }}" 
                                       class="form-input rounded-l-none @error('group_number') border-red-500 @enderror" 
                                       placeholder="01" maxlength="10">
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Users will dial *6{{ old('group_number', $suggestedNumber ?? 'XX') }} to reach this group
                            </p>
                            @error('group_number')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" id="description" rows="2" 
                                  class="form-input @error('description') border-red-500 @enderror" 
                                  placeholder="Optional description for this group">{{ old('description') }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="ring_strategy" class="form-label">Ring Strategy <span class="text-red-500">*</span></label>
                            <select name="ring_strategy" id="ring_strategy" class="form-select" required>
                                @foreach($ringStrategies ?? \App\Models\ExtensionGroup::RING_STRATEGIES as $value => $label)
                                    <option value="{{ $value }}" {{ old('ring_strategy', 'ringall') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="ring_time" class="form-label">Ring Timeout</label>
                            <div class="flex items-center">
                                <input type="number" name="ring_time" id="ring_time" 
                                       value="{{ old('ring_time', 30) }}" 
                                       class="form-input" min="5" max="300" required>
                                <span class="ml-2 text-sm text-gray-500">seconds</span>
                            </div>
                        </div>

                        <div>
                            <label for="pickup_group" class="form-label">Pickup Group</label>
                            <input type="number" name="pickup_group" id="pickup_group" 
                                   value="{{ old('pickup_group') }}" 
                                   class="form-input" min="1" max="63" placeholder="1-63">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Members can pickup each other's calls with *8
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced Settings -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Advanced Settings</h3>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="music_on_hold" class="form-label">Music on Hold</label>
                            <select name="music_on_hold" id="music_on_hold" class="form-select">
                                <option value="default" {{ old('music_on_hold', 'default') === 'default' ? 'selected' : '' }}>Default</option>
                                <option value="classical" {{ old('music_on_hold') === 'classical' ? 'selected' : '' }}>Classical</option>
                                <option value="jazz" {{ old('music_on_hold') === 'jazz' ? 'selected' : '' }}>Jazz</option>
                                <option value="none" {{ old('music_on_hold') === 'none' ? 'selected' : '' }}>None (Silence)</option>
                            </select>
                        </div>

                        <div class="flex flex-col justify-center space-y-3">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="record_calls" value="1" 
                                       {{ old('record_calls') ? 'checked' : '' }}
                                       class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Record all calls to this group</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="announce_holdtime" value="1" 
                                       {{ old('announce_holdtime') ? 'checked' : '' }}
                                       class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Announce estimated hold time</span>
                            </label>
                        </div>
                    </div>

                    <!-- Timeout Destination -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">No Answer Destination</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="timeout_destination_type" class="form-label">Destination Type</label>
                                <select name="timeout_destination_type" id="timeout_destination_type" class="form-select" 
                                        onchange="toggleDestinationSelect('timeout')">
                                    <option value="">Voicemail (Default)</option>
                                    <option value="extension" {{ old('timeout_destination_type') === 'extension' ? 'selected' : '' }}>Extension</option>
                                    <option value="queue" {{ old('timeout_destination_type') === 'queue' ? 'selected' : '' }}>Queue</option>
                                    <option value="hangup" {{ old('timeout_destination_type') === 'hangup' ? 'selected' : '' }}>Hangup</option>
                                </select>
                            </div>
                            <div id="timeout_extension_select" style="{{ old('timeout_destination_type') === 'extension' ? '' : 'display:none' }}">
                                <label class="form-label">Select Extension</label>
                                <select name="timeout_destination_id" class="form-select">
                                    @foreach($availableExtensions ?? [] as $ext)
                                        <option value="{{ $ext->id }}" {{ old('timeout_destination_id') == $ext->id ? 'selected' : '' }}>
                                            {{ $ext->extension }} - {{ $ext->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div id="timeout_queue_select" style="{{ old('timeout_destination_type') === 'queue' ? '' : 'display:none' }}">
                                <label class="form-label">Select Queue</label>
                                <select name="timeout_destination_id" class="form-select">
                                    @foreach($availableQueues ?? [] as $queue)
                                        <option value="{{ $queue->id }}" {{ old('timeout_destination_id') == $queue->id ? 'selected' : '' }}>
                                            {{ $queue->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Group Members -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Group Members</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Select extensions to include in this group (order determines priority for hunt strategies)</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-80 overflow-y-auto">
                        @foreach($extensions ?? [] as $extension)
                            <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors border border-transparent hover:border-primary-300 dark:hover:border-primary-600">
                                <input type="checkbox" name="extensions[]" value="{{ $extension->id }}" 
                                       {{ in_array($extension->id, old('extensions', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                                <span class="ml-3 flex-1">
                                    <span class="flex items-center justify-between">
                                        <span class="font-mono font-medium text-gray-900 dark:text-white">{{ $extension->extension }}</span>
                                        <span class="w-2 h-2 rounded-full {{ $extension->status === 'online' ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                    </span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $extension->name }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @if(empty($extensions) || count($extensions) === 0)
                        <p class="text-center text-gray-500 dark:text-gray-400 py-4">
                            No extensions available. <a href="{{ route('extensions.create') }}" class="text-primary-600 hover:underline">Create extensions first</a>.
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

    <script>
        function toggleDestinationSelect(prefix) {
            const type = document.getElementById(prefix + '_destination_type').value;
            document.getElementById(prefix + '_extension_select').style.display = type === 'extension' ? '' : 'none';
            document.getElementById(prefix + '_queue_select').style.display = type === 'queue' ? '' : 'none';
        }
    </script>
</x-app-layout>
