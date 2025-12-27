<x-app-layout>
    @section('title', 'Create DID')
    @section('page-title', 'Create DID')

    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('dids.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Create DID</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add a new Direct Inward Dialing number</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <form action="{{ route('dids.store') }}" method="POST" class="space-y-6" x-data="{ destinationType: '{{ old('destination_type', 'extension') }}' }">
            @csrf

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">DID Details</h3>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="did_number" class="form-label">DID Number <span class="text-red-500">*</span></label>
                            <input type="text" name="did_number" id="did_number" value="{{ old('did_number') }}" 
                                   class="form-input @error('did_number') border-red-500 @enderror" 
                                   placeholder="e.g., +15551234567" required>
                            @error('did_number')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="carrier_id" class="form-label">Carrier</label>
                            <select name="carrier_id" id="carrier_id" class="form-select">
                                <option value="">-- Select Carrier --</option>
                                @foreach($carriers ?? [] as $carrier)
                                    <option value="{{ $carrier->id }}" {{ old('carrier_id') == $carrier->id ? 'selected' : '' }}>
                                        {{ $carrier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="description" class="form-label">Description</label>
                        <input type="text" name="description" id="description" value="{{ old('description') }}" 
                               class="form-input" placeholder="e.g., Main Sales Line">
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
                        <label for="destination_extension" class="form-label">Select Extension</label>
                        <select name="destination_id" class="form-select" x-bind:disabled="destinationType !== 'extension'">
                            <option value="">-- Select Extension --</option>
                            @foreach($extensions ?? [] as $extension)
                                <option value="{{ $extension->id }}" {{ old('destination_id') == $extension->id ? 'selected' : '' }}>
                                    {{ $extension->extension }} - {{ $extension->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Extension Group Destination -->
                    <div x-show="destinationType === 'extension_group'">
                        <label for="destination_group" class="form-label">Select Extension Group</label>
                        <select name="destination_id" class="form-select" x-bind:disabled="destinationType !== 'extension_group'">
                            <option value="">-- Select Group --</option>
                            @foreach($extensionGroups ?? [] as $group)
                                <option value="{{ $group->id }}" {{ old('destination_id') == $group->id ? 'selected' : '' }}>
                                    {{ $group->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Queue Destination -->
                    <div x-show="destinationType === 'queue'">
                        <label for="destination_queue" class="form-label">Select Queue</label>
                        <select name="destination_id" class="form-select" x-bind:disabled="destinationType !== 'queue'">
                            <option value="">-- Select Queue --</option>
                            @foreach($queues ?? [] as $queue)
                                <option value="{{ $queue->id }}" {{ old('destination_id') == $queue->id ? 'selected' : '' }}>
                                    {{ $queue->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Ring Tree Destination -->
                    <div x-show="destinationType === 'ring_tree'">
                        <label for="destination_ring_tree" class="form-label">Select Ring Tree</label>
                        <select name="destination_id" class="form-select" x-bind:disabled="destinationType !== 'ring_tree'">
                            <option value="">-- Select Ring Tree --</option>
                            @foreach($ringTrees ?? [] as $tree)
                                <option value="{{ $tree->id }}" {{ old('destination_id') == $tree->id ? 'selected' : '' }}>
                                    {{ $tree->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- IVR Destination -->
                    <div x-show="destinationType === 'ivr'">
                        <label for="destination_ivr" class="form-label">Select IVR</label>
                        <select name="destination_id" class="form-select" x-bind:disabled="destinationType !== 'ivr'">
                            <option value="">-- Select IVR --</option>
                            @foreach($ivrs ?? [] as $ivr)
                                <option value="{{ $ivr->id }}" {{ old('destination_id') == $ivr->id ? 'selected' : '' }}>
                                    {{ $ivr->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Block Filter Destination -->
                    <div x-show="destinationType === 'block_filter'">
                        <label for="destination_block_filter" class="form-label">Select Block Filter Group</label>
                        <select name="destination_id" class="form-select" x-bind:disabled="destinationType !== 'block_filter'">
                            <option value="">-- Select Block Filter Group --</option>
                            @foreach($blockFilterGroups ?? [] as $group)
                                <option value="{{ $group->id }}" {{ old('destination_id') == $group->id ? 'selected' : '' }}>
                                    {{ $group->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Advanced Routing (Optional)</h3>
                </div>
                <div class="p-6 space-y-6">
                    <div class="flex items-center">
                        <input type="checkbox" name="time_based_routing" id="time_based_routing" value="1" 
                               {{ old('time_based_routing') ? 'checked' : '' }}
                               class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                        <label for="time_based_routing" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                            Enable Time-Based Routing
                        </label>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Configure different routing based on time of day after saving the DID.
                    </p>

                    <div class="flex items-center">
                        <input type="checkbox" name="caller_id_routing" id="caller_id_routing" value="1" 
                               {{ old('caller_id_routing') ? 'checked' : '' }}
                               class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                        <label for="caller_id_routing" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                            Enable Caller ID Based Routing
                        </label>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Route calls differently based on caller ID (VIP routing) after saving the DID.
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('dids.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Create DID
                </button>
            </div>
        </form>
    </div>
</x-app-layout>



