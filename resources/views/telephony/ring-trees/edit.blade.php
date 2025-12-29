<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('ring-trees.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Edit Ring Tree: {{ $ringTree->name ?? '' }}
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <form action="{{ route('ring-trees.update', $ringTree ?? 0) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="text-lg font-medium">Basic Information</h3>
                    </div>
                    <div class="card-body space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="form-label">Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" id="name" 
                                       value="{{ old('name', $ringTree->name ?? '') }}" 
                                       class="form-input @error('name') border-red-500 @enderror" required>
                                @error('name')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="is_active" class="form-label">Status</label>
                                <select name="is_active" id="is_active" class="form-select">
                                    <option value="1" {{ old('is_active', $ringTree->is_active ?? true) ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ !old('is_active', $ringTree->is_active ?? true) ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" rows="3" 
                                      class="form-input @error('description') border-red-500 @enderror">{{ old('description', $ringTree->description ?? '') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Ring Tree Builder -->
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="text-lg font-medium">Ring Tree Nodes</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Configure the ring sequence. Calls will ring each level in order.
                        </p>
                    </div>
                    <div class="card-body">
                        <div id="ring-tree-builder" class="space-y-4">
                            @php $nodes = old('nodes', ($ringTree->nodes ?? collect())->toArray()); @endphp
                            @for($i = 0; $i < 3; $i++)
                                <div class="ring-level p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="font-medium text-gray-900 dark:text-white">Level {{ $i + 1 }}</h4>
                                        <span class="text-sm text-gray-500">
                                            @if($i === 0) First ring attempt @else If Level {{ $i }} doesn't answer @endif
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="form-label">Destination Type</label>
                                            <select name="nodes[{{ $i }}][destination_type]" class="form-select destination-type">
                                                @if($i > 0)<option value="">None (skip)</option>@endif
                                                <option value="extension" {{ ($nodes[$i]['destination_type'] ?? '') === 'extension' ? 'selected' : '' }}>Extension</option>
                                                <option value="extension_group" {{ ($nodes[$i]['destination_type'] ?? '') === 'extension_group' ? 'selected' : '' }}>Extension Group</option>
                                                <option value="queue" {{ ($nodes[$i]['destination_type'] ?? '') === 'queue' ? 'selected' : '' }}>Queue</option>
                                                <option value="external" {{ ($nodes[$i]['destination_type'] ?? '') === 'external' ? 'selected' : '' }}>External Number</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label">Destination</label>
                                            <select name="nodes[{{ $i }}][destination_id]" class="form-select">
                                                <option value="">Select destination</option>
                                                @foreach($extensions ?? [] as $ext)
                                                    <option value="{{ $ext->id }}" {{ ($nodes[$i]['destination_id'] ?? '') == $ext->id ? 'selected' : '' }}>
                                                        {{ $ext->extension }} - {{ $ext->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label">Ring Time (seconds)</label>
                                            <input type="number" name="nodes[{{ $i }}][ring_time]" 
                                                   value="{{ $nodes[$i]['ring_time'] ?? 20 }}" 
                                                   min="5" max="120" class="form-input">
                                        </div>
                                    </div>
                                </div>
                            @endfor
                        </div>

                        <!-- Final Destination -->
                        <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                            <h4 class="font-medium text-amber-800 dark:text-amber-200 mb-4">Final Destination (if no answer)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">Action</label>
                                    <select name="final_destination_type" class="form-select">
                                        <option value="voicemail" {{ old('final_destination_type', $ringTree->final_destination_type ?? '') === 'voicemail' ? 'selected' : '' }}>Go to Voicemail</option>
                                        <option value="hangup" {{ old('final_destination_type', $ringTree->final_destination_type ?? '') === 'hangup' ? 'selected' : '' }}>Hangup</option>
                                        <option value="external" {{ old('final_destination_type', $ringTree->final_destination_type ?? '') === 'external' ? 'selected' : '' }}>External Number</option>
                                        <option value="announcement" {{ old('final_destination_type', $ringTree->final_destination_type ?? '') === 'announcement' ? 'selected' : '' }}>Play Announcement</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Destination</label>
                                    <input type="text" name="final_destination_value" 
                                           value="{{ old('final_destination_value', $ringTree->final_destination_value ?? '') }}"
                                           class="form-input" placeholder="e.g., extension number or external number">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('ring-trees.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">Update Ring Tree</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>







