<x-app-layout>
    @section('title', 'Edit DID')
    @section('page-title', 'Edit DID')

    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('dids.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Edit {{ $did->did_number }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Update DID configuration and routing</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <form action="{{ route('dids.update', $did) }}" method="POST" class="space-y-6" x-data="{ destinationType: '{{ old('destination_type', $did->destination_type) }}' }">
            @csrf
            @method('PUT')

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">DID Details</h3>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="did_number" class="form-label">DID Number <span class="text-red-500">*</span></label>
                            <input type="text" name="did_number" id="did_number" 
                                   value="{{ old('did_number', $did->did_number) }}" 
                                   class="form-input @error('did_number') border-red-500 @enderror" required>
                            @error('did_number')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="carrier_id" class="form-label">Carrier</label>
                            <select name="carrier_id" id="carrier_id" class="form-select">
                                <option value="">-- Select Carrier --</option>
                                @foreach($carriers ?? [] as $carrier)
                                    <option value="{{ $carrier->id }}" {{ old('carrier_id', $did->carrier_id) == $carrier->id ? 'selected' : '' }}>
                                        {{ $carrier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="description" class="form-label">Description</label>
                        <input type="text" name="description" id="description" 
                               value="{{ old('description', $did->description) }}" 
                               class="form-input" placeholder="e.g., Main Sales Line">
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1" 
                               {{ old('is_active', $did->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                        <label for="is_active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                            Active
                        </label>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Routing Configuration</h3>
                </div>
                <div class="p-6 space-y-6">
                    <div>
                        <label for="destination_type" class="form-label">Destination Type <span class="text-red-500">*</span></label>
                        <select name="destination_type" id="destination_type" class="form-select" x-model="destinationType" required>
                            <option value="extension">Extension</option>
                            <option value="extension_group">Extension Group</option>
                            <option value="queue">Call Queue</option>
                            <option value="ring_tree">Ring Tree</option>
                            <option value="ivr">IVR</option>
                            <option value="voicemail">Voicemail</option>
                            <option value="hangup">Hangup</option>
                            <option value="block_filter">Block Filter Group</option>
                        </select>
                    </div>

                    <!-- Extension Destination -->
                    <div x-show="destinationType === 'extension'">
                        <label class="form-label">Select Extension</label>
                        <select name="destination_id" class="form-select" x-bind:disabled="destinationType !== 'extension'">
                            <option value="">-- Select Extension --</option>
                            @foreach($extensions ?? [] as $extension)
                                <option value="{{ $extension->id }}" {{ old('destination_id', $did->destination_id) == $extension->id && $did->destination_type === 'extension' ? 'selected' : '' }}>
                                    {{ $extension->extension }} - {{ $extension->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Extension Group Destination -->
                    <div x-show="destinationType === 'extension_group'">
                        <label class="form-label">Select Extension Group</label>
                        <select name="destination_id" class="form-select" x-bind:disabled="destinationType !== 'extension_group'">
                            <option value="">-- Select Group --</option>
                            @foreach($extensionGroups ?? [] as $group)
                                <option value="{{ $group->id }}" {{ old('destination_id', $did->destination_id) == $group->id && $did->destination_type === 'extension_group' ? 'selected' : '' }}>
                                    {{ $group->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Queue Destination -->
                    <div x-show="destinationType === 'queue'">
                        <label class="form-label">Select Queue</label>
                        <select name="destination_id" class="form-select" x-bind:disabled="destinationType !== 'queue'">
                            <option value="">-- Select Queue --</option>
                            @foreach($queues ?? [] as $queue)
                                <option value="{{ $queue->id }}" {{ old('destination_id', $did->destination_id) == $queue->id && $did->destination_type === 'queue' ? 'selected' : '' }}>
                                    {{ $queue->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Ring Tree Destination -->
                    <div x-show="destinationType === 'ring_tree'">
                        <label class="form-label">Select Ring Tree</label>
                        <select name="destination_id" class="form-select" x-bind:disabled="destinationType !== 'ring_tree'">
                            <option value="">-- Select Ring Tree --</option>
                            @foreach($ringTrees ?? [] as $tree)
                                <option value="{{ $tree->id }}" {{ old('destination_id', $did->destination_id) == $tree->id && $did->destination_type === 'ring_tree' ? 'selected' : '' }}>
                                    {{ $tree->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- IVR Destination -->
                    <div x-show="destinationType === 'ivr'">
                        <label class="form-label">Select IVR</label>
                        <select name="destination_id" class="form-select" x-bind:disabled="destinationType !== 'ivr'">
                            <option value="">-- Select IVR --</option>
                            @foreach($ivrs ?? [] as $ivr)
                                <option value="{{ $ivr->id }}" {{ old('destination_id', $did->destination_id) == $ivr->id && $did->destination_type === 'ivr' ? 'selected' : '' }}>
                                    {{ $ivr->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Block Filter Destination -->
                    <div x-show="destinationType === 'block_filter'">
                        <label class="form-label">Select Block Filter Group</label>
                        <select name="destination_id" class="form-select" x-bind:disabled="destinationType !== 'block_filter'">
                            <option value="">-- Select Block Filter Group --</option>
                            @foreach($blockFilterGroups ?? [] as $group)
                                <option value="{{ $group->id }}" {{ old('destination_id', $did->destination_id) == $group->id && $did->destination_type === 'block_filter' ? 'selected' : '' }}>
                                    {{ $group->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <form action="{{ route('dids.destroy', $did) }}" method="POST" 
                      onsubmit="return confirm('Are you sure you want to delete this DID?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete DID
                    </button>
                </form>

                <div class="flex items-center space-x-3">
                    <a href="{{ route('dids.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Update DID
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>

