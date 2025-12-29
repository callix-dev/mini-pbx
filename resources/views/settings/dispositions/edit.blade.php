<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('dispositions.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Edit Disposition: {{ $disposition->name ?? '' }}
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <form action="{{ route('dispositions.update', $disposition ?? 0) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-lg font-medium">Disposition Details</h3>
                    </div>
                    <div class="card-body space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="form-label">Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" id="name" 
                                       value="{{ old('name', $disposition->name ?? '') }}" 
                                       class="form-input @error('name') border-red-500 @enderror" required>
                                @error('name')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="code" class="form-label">Code <span class="text-red-500">*</span></label>
                                <input type="text" name="code" id="code" 
                                       value="{{ old('code', $disposition->code ?? '') }}" 
                                       class="form-input @error('code') border-red-500 @enderror" 
                                       maxlength="20" required style="text-transform: uppercase;">
                                @error('code')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="color" class="form-label">Color <span class="text-red-500">*</span></label>
                            <div class="flex items-center gap-4">
                                <input type="color" name="color" id="color" 
                                       value="{{ old('color', $disposition->color ?? '#6366f1') }}" 
                                       class="h-10 w-20 rounded border border-gray-300 cursor-pointer">
                                <input type="text" id="color-hex" 
                                       value="{{ old('color', $disposition->color ?? '#6366f1') }}" 
                                       class="form-input w-32 font-mono">
                                
                                <div class="flex gap-2">
                                    @foreach(['#10b981', '#ef4444', '#f59e0b', '#6366f1', '#8b5cf6', '#ec4899', '#0ea5e9', '#64748b'] as $preset)
                                        <button type="button" class="preset-color w-8 h-8 rounded-full border-2 border-transparent hover:border-gray-400"
                                                style="background-color: {{ $preset }}" data-color="{{ $preset }}"></button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="form-label">Options</label>
                                <div class="space-y-3 mt-2">
                                    <label class="flex items-center">
                                        <input type="checkbox" name="requires_callback" value="1" 
                                               {{ old('requires_callback', $disposition->requires_callback ?? false) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        <span class="ml-2 text-gray-700 dark:text-gray-300">Requires Callback</span>
                                    </label>
                                    
                                    <label class="flex items-center">
                                        <input type="checkbox" name="is_default" value="1" 
                                               {{ old('is_default', $disposition->is_default ?? false) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        <span class="ml-2 text-gray-700 dark:text-gray-300">Set as Default</span>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label for="is_active" class="form-label">Status</label>
                                <select name="is_active" id="is_active" class="form-select">
                                    <option value="1" {{ old('is_active', $disposition->is_active ?? true) ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ !old('is_active', $disposition->is_active ?? true) ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>

                        <!-- Stats -->
                        @if(isset($disposition))
                            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Usage Statistics</h4>
                                <div class="grid grid-cols-3 gap-4 text-center">
                                    <div>
                                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $disposition->calls_count ?? 0 }}</p>
                                        <p class="text-sm text-gray-500">Total Uses</p>
                                    </div>
                                    <div>
                                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $disposition->created_at?->format('M d, Y') ?? '-' }}</p>
                                        <p class="text-sm text-gray-500">Created</p>
                                    </div>
                                    <div>
                                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $disposition->updated_at?->format('M d, Y') ?? '-' }}</p>
                                        <p class="text-sm text-gray-500">Last Updated</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="card-footer flex items-center justify-end gap-4">
                        <a href="{{ route('dispositions.index') }}" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">Update Disposition</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const colorInput = document.getElementById('color');
            const colorHex = document.getElementById('color-hex');
            const codeInput = document.getElementById('code');

            colorInput.addEventListener('input', () => colorHex.value = colorInput.value);
            colorHex.addEventListener('input', () => {
                if (/^#[0-9A-Fa-f]{6}$/.test(colorHex.value)) colorInput.value = colorHex.value;
            });

            document.querySelectorAll('.preset-color').forEach(btn => {
                btn.addEventListener('click', () => {
                    colorInput.value = btn.dataset.color;
                    colorHex.value = btn.dataset.color;
                });
            });

            codeInput.addEventListener('input', () => codeInput.value = codeInput.value.toUpperCase());
        });
    </script>
</x-app-layout>







