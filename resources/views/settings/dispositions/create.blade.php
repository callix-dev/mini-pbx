<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('dispositions.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Create Disposition
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <form action="{{ route('dispositions.store') }}" method="POST">
                @csrf
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-lg font-medium">Disposition Details</h3>
                    </div>
                    <div class="card-body space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="form-label">Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" 
                                       class="form-input @error('name') border-red-500 @enderror" 
                                       placeholder="e.g., Sold, Not Interested" required>
                                @error('name')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="code" class="form-label">Code <span class="text-red-500">*</span></label>
                                <input type="text" name="code" id="code" value="{{ old('code') }}" 
                                       class="form-input @error('code') border-red-500 @enderror" 
                                       placeholder="e.g., SOLD, NI" maxlength="20" required
                                       style="text-transform: uppercase;">
                                <p class="mt-1 text-sm text-gray-500">Short code for reports (auto-uppercase)</p>
                                @error('code')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="color" class="form-label">Color <span class="text-red-500">*</span></label>
                            <div class="flex items-center gap-4">
                                <input type="color" name="color" id="color" value="{{ old('color', '#6366f1') }}" 
                                       class="h-10 w-20 rounded border border-gray-300 cursor-pointer">
                                <input type="text" id="color-hex" value="{{ old('color', '#6366f1') }}" 
                                       class="form-input w-32 font-mono" placeholder="#000000">
                                
                                <!-- Preset Colors -->
                                <div class="flex gap-2">
                                    @foreach(['#10b981', '#ef4444', '#f59e0b', '#6366f1', '#8b5cf6', '#ec4899', '#0ea5e9', '#64748b'] as $preset)
                                        <button type="button" class="preset-color w-8 h-8 rounded-full border-2 border-transparent hover:border-gray-400 transition-colors"
                                                style="background-color: {{ $preset }}" data-color="{{ $preset }}"></button>
                                    @endforeach
                                </div>
                            </div>
                            @error('color')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="form-label">Options</label>
                                <div class="space-y-3 mt-2">
                                    <label class="flex items-center">
                                        <input type="checkbox" name="requires_callback" value="1" {{ old('requires_callback') ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        <span class="ml-2 text-gray-700 dark:text-gray-300">Requires Callback</span>
                                    </label>
                                    <p class="text-xs text-gray-500 ml-6">Agent must schedule a callback when selecting this disposition</p>
                                    
                                    <label class="flex items-center">
                                        <input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        <span class="ml-2 text-gray-700 dark:text-gray-300">Set as Default</span>
                                    </label>
                                    <p class="text-xs text-gray-500 ml-6">This disposition will be pre-selected for agents</p>
                                </div>
                            </div>

                            <div>
                                <label for="is_active" class="form-label">Status</label>
                                <select name="is_active" id="is_active" class="form-select">
                                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>

                        <!-- Preview -->
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Preview:</p>
                            <div class="flex items-center gap-3">
                                <span id="preview-badge" class="px-3 py-1 rounded-full text-white font-medium" 
                                      style="background-color: {{ old('color', '#6366f1') }}">
                                    {{ old('name', 'Disposition Name') }}
                                </span>
                                <span class="font-mono text-sm text-gray-500" id="preview-code">{{ old('code', 'CODE') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer flex items-center justify-end gap-4">
                        <a href="{{ route('dispositions.index') }}" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">Create Disposition</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const colorInput = document.getElementById('color');
            const colorHex = document.getElementById('color-hex');
            const nameInput = document.getElementById('name');
            const codeInput = document.getElementById('code');
            const previewBadge = document.getElementById('preview-badge');
            const previewCode = document.getElementById('preview-code');

            // Sync color inputs
            colorInput.addEventListener('input', () => {
                colorHex.value = colorInput.value;
                previewBadge.style.backgroundColor = colorInput.value;
            });

            colorHex.addEventListener('input', () => {
                if (/^#[0-9A-Fa-f]{6}$/.test(colorHex.value)) {
                    colorInput.value = colorHex.value;
                    previewBadge.style.backgroundColor = colorHex.value;
                }
            });

            // Preset colors
            document.querySelectorAll('.preset-color').forEach(btn => {
                btn.addEventListener('click', () => {
                    colorInput.value = btn.dataset.color;
                    colorHex.value = btn.dataset.color;
                    previewBadge.style.backgroundColor = btn.dataset.color;
                });
            });

            // Update preview
            nameInput.addEventListener('input', () => {
                previewBadge.textContent = nameInput.value || 'Disposition Name';
            });

            codeInput.addEventListener('input', () => {
                codeInput.value = codeInput.value.toUpperCase();
                previewCode.textContent = codeInput.value || 'CODE';
            });
        });
    </script>
</x-app-layout>

