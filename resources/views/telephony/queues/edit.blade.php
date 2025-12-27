<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('queues.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Edit Queue: {{ $queue->name ?? '' }}
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <form action="{{ route('queues.update', $queue ?? 0) }}" method="POST">
                @csrf
                @method('PUT')
                
                <!-- Basic Information -->
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="text-lg font-medium">Basic Information</h3>
                    </div>
                    <div class="card-body space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="form-label">Queue Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" id="name" 
                                       value="{{ old('name', $queue->name ?? '') }}" 
                                       class="form-input @error('name') border-red-500 @enderror" required>
                                @error('name')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="number" class="form-label">Queue Number <span class="text-red-500">*</span></label>
                                <input type="text" name="number" id="number" 
                                       value="{{ old('number', $queue->number ?? '') }}" 
                                       class="form-input @error('number') border-red-500 @enderror" required>
                                @error('number')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" rows="2" 
                                      class="form-input">{{ old('description', $queue->description ?? '') }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="strategy" class="form-label">Ring Strategy</label>
                                <select name="strategy" id="strategy" class="form-select">
                                    <option value="ringall" {{ old('strategy', $queue->strategy ?? '') === 'ringall' ? 'selected' : '' }}>Ring All</option>
                                    <option value="leastrecent" {{ old('strategy', $queue->strategy ?? '') === 'leastrecent' ? 'selected' : '' }}>Least Recent</option>
                                    <option value="fewestcalls" {{ old('strategy', $queue->strategy ?? '') === 'fewestcalls' ? 'selected' : '' }}>Fewest Calls</option>
                                    <option value="random" {{ old('strategy', $queue->strategy ?? '') === 'random' ? 'selected' : '' }}>Random</option>
                                    <option value="rrmemory" {{ old('strategy', $queue->strategy ?? '') === 'rrmemory' ? 'selected' : '' }}>Round Robin Memory</option>
                                    <option value="linear" {{ old('strategy', $queue->strategy ?? '') === 'linear' ? 'selected' : '' }}>Linear</option>
                                    <option value="wrandom" {{ old('strategy', $queue->strategy ?? '') === 'wrandom' ? 'selected' : '' }}>Weighted Random</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="is_active" class="form-label">Status</label>
                                <select name="is_active" id="is_active" class="form-select">
                                    <option value="1" {{ old('is_active', $queue->is_active ?? true) ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ !old('is_active', $queue->is_active ?? true) ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timing Settings -->
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="text-lg font-medium">Timing Settings</h3>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="timeout" class="form-label">Ring Timeout (seconds)</label>
                                <input type="number" name="timeout" id="timeout" 
                                       value="{{ old('timeout', $queue->timeout ?? 30) }}" 
                                       min="5" max="300" class="form-input">
                            </div>
                            <div>
                                <label for="wrapuptime" class="form-label">Wrap-up Time (seconds)</label>
                                <input type="number" name="wrapuptime" id="wrapuptime" 
                                       value="{{ old('wrapuptime', $queue->wrapuptime ?? 0) }}" 
                                       min="0" max="300" class="form-input">
                            </div>
                            <div>
                                <label for="max_wait_time" class="form-label">Max Wait Time (seconds)</label>
                                <input type="number" name="max_wait_time" id="max_wait_time" 
                                       value="{{ old('max_wait_time', $queue->max_wait_time ?? 300) }}" 
                                       min="0" max="3600" class="form-input">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Queue Members -->
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="text-lg font-medium">Queue Members</h3>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($extensions ?? [] as $extension)
                                <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <input type="checkbox" name="members[]" value="{{ $extension->id }}"
                                           {{ in_array($extension->id, old('members', ($queue->members ?? collect())->pluck('extension_id')->toArray())) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    <span class="ml-3">
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $extension->extension }}</span>
                                        <span class="text-gray-500 dark:text-gray-400"> - {{ $extension->name }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @if(($extensions ?? collect())->isEmpty())
                            <p class="text-gray-500 dark:text-gray-400 text-center py-4">No extensions available. Create extensions first.</p>
                        @endif
                    </div>
                </div>

                <!-- Hold Music -->
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="text-lg font-medium">Hold Music & Announcements</h3>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="hold_music_id" class="form-label">Hold Music</label>
                                <select name="hold_music_id" id="hold_music_id" class="form-select">
                                    <option value="">Default</option>
                                    @foreach($holdMusic ?? [] as $music)
                                        <option value="{{ $music->id }}" {{ old('hold_music_id', $queue->hold_music_id ?? '') == $music->id ? 'selected' : '' }}>
                                            {{ $music->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="announce_frequency" class="form-label">Position Announce Frequency (seconds)</label>
                                <input type="number" name="announce_frequency" id="announce_frequency" 
                                       value="{{ old('announce_frequency', $queue->announce_frequency ?? 30) }}" 
                                       min="0" max="300" class="form-input">
                                <p class="mt-1 text-sm text-gray-500">0 = disabled</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('queues.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">Update Queue</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>





