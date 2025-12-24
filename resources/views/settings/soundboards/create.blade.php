<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('soundboards.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Create Soundboard
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <form action="{{ route('soundboards.store') }}" method="POST">
                @csrf
                
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="text-lg font-medium">Soundboard Details</h3>
                    </div>
                    <div class="card-body space-y-6">
                        <div>
                            <label for="name" class="form-label">Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" 
                                   class="form-input @error('name') border-red-500 @enderror" 
                                   placeholder="e.g., Sales Responses" required>
                            @error('name')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" rows="3" 
                                      class="form-input" placeholder="Describe the purpose of this soundboard">{{ old('description') }}</textarea>
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

                <!-- Queue Assignment -->
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="text-lg font-medium">Queue Assignment</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Select which queues this soundboard should be available to
                        </p>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @forelse($queues ?? [] as $queue)
                                <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <input type="checkbox" name="queues[]" value="{{ $queue->id }}"
                                           {{ in_array($queue->id, old('queues', [])) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    <span class="ml-3">
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $queue->name }}</span>
                                        <span class="text-gray-500 dark:text-gray-400 text-sm block">{{ $queue->number }}</span>
                                    </span>
                                </label>
                            @empty
                                <p class="col-span-full text-gray-500 dark:text-gray-400 text-center py-4">
                                    No queues available. Create queues first.
                                </p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('soundboards.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">Create Soundboard</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

