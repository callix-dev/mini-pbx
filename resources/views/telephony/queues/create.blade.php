<x-app-layout>
    @section('title', 'Create Queue')
    @section('page-title', 'Create Queue')

    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('queues.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Create Call Queue</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Configure a new call queue with agents</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto">
        <form action="{{ route('queues.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Queue Details</h3>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="form-label">Queue Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" 
                                   class="form-input @error('name') border-red-500 @enderror" 
                                   placeholder="e.g., Sales Queue" required>
                            @error('name')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="strategy" class="form-label">Ring Strategy <span class="text-red-500">*</span></label>
                            <select name="strategy" id="strategy" class="form-select" required>
                                <option value="ringall" {{ old('strategy') === 'ringall' ? 'selected' : '' }}>Ring All</option>
                                <option value="leastrecent" {{ old('strategy') === 'leastrecent' ? 'selected' : '' }}>Least Recent</option>
                                <option value="fewestcalls" {{ old('strategy') === 'fewestcalls' ? 'selected' : '' }}>Fewest Calls</option>
                                <option value="random" {{ old('strategy') === 'random' ? 'selected' : '' }}>Random</option>
                                <option value="rrmemory" {{ old('strategy') === 'rrmemory' ? 'selected' : '' }}>Round Robin</option>
                                <option value="linear" {{ old('strategy') === 'linear' ? 'selected' : '' }}>Linear</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" id="description" rows="2" 
                                  class="form-input" placeholder="Optional description">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Queue Settings</h3>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="timeout" class="form-label">Ring Timeout (seconds)</label>
                            <input type="number" name="timeout" id="timeout" value="{{ old('timeout', 30) }}" 
                                   class="form-input" min="5" max="300">
                        </div>

                        <div>
                            <label for="retry" class="form-label">Retry Delay (seconds)</label>
                            <input type="number" name="retry" id="retry" value="{{ old('retry', 5) }}" 
                                   class="form-input" min="0" max="60">
                        </div>

                        <div>
                            <label for="wrapup_time" class="form-label">Wrap-up Time (seconds)</label>
                            <input type="number" name="wrapup_time" id="wrapup_time" value="{{ old('wrapup_time', 0) }}" 
                                   class="form-input" min="0" max="300">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="max_wait_time" class="form-label">Max Wait Time (seconds)</label>
                            <input type="number" name="max_wait_time" id="max_wait_time" value="{{ old('max_wait_time', 300) }}" 
                                   class="form-input" min="0">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">0 = unlimited</p>
                        </div>

                        <div>
                            <label for="max_callers" class="form-label">Max Callers in Queue</label>
                            <input type="number" name="max_callers" id="max_callers" value="{{ old('max_callers', 0) }}" 
                                   class="form-input" min="0">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">0 = unlimited</p>
                        </div>
                    </div>

                    <div>
                        <label for="hold_music_id" class="form-label">Hold Music</label>
                        <select name="hold_music_id" id="hold_music_id" class="form-select">
                            <option value="">Default Hold Music</option>
                            @foreach($holdMusic ?? [] as $music)
                                <option value="{{ $music->id }}" {{ old('hold_music_id') == $music->id ? 'selected' : '' }}>
                                    {{ $music->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="announce_position" value="1" 
                                   {{ old('announce_position', true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Announce position</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="announce_holdtime" value="1" 
                                   {{ old('announce_holdtime') ? 'checked' : '' }}
                                   class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Announce hold time</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="allow_callback" value="1" 
                                   {{ old('allow_callback') ? 'checked' : '' }}
                                   class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Allow callback request</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" 
                                   {{ old('is_active', true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Queue Agents</h3>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Select extensions to add as queue agents. You can also add agents after creating the queue.
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-64 overflow-y-auto">
                        @foreach($extensions ?? [] as $extension)
                            <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                <input type="checkbox" name="agents[]" value="{{ $extension->id }}" 
                                       {{ in_array($extension->id, old('agents', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                                <span class="ml-3">
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">{{ $extension->extension }}</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $extension->name }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('queues.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Create Queue
                </button>
            </div>
        </form>
    </div>
</x-app-layout>

