<x-app-layout>
    @section('title', 'Quick Setup - Carriers')
    @section('page-title', 'Quick Setup')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Quick Carrier Setup</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Select a provider to quickly configure your SIP trunk
                </p>
            </div>
            <a href="{{ route('carriers.index') }}" class="btn-secondary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Carriers
            </a>
        </div>
    </x-slot>

    <div x-data="quickSetup()" x-init="init()">
        <!-- Direction Tabs -->
        <div class="mb-6">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex space-x-8">
                    <button @click="direction = 'outbound'" 
                            :class="direction === 'outbound' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                        Outbound Trunks
                    </button>
                    <button @click="direction = 'inbound'"
                            :class="direction === 'inbound' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"/>
                        </svg>
                        Inbound Trunks
                    </button>
                </nav>
            </div>
        </div>

        <!-- Provider Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($providers as $slug => $provider)
                @php
                    $hasOutbound = isset($provider['templates']['outbound']);
                    $hasInbound = isset($provider['templates']['inbound']);
                @endphp
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow cursor-pointer group"
                     @click="openSetupModal('{{ $slug }}', direction)"
                     x-show="(direction === 'outbound' && {{ $hasOutbound ? 'true' : 'false' }}) || (direction === 'inbound' && {{ $hasInbound ? 'true' : 'false' }})"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100">
                    <div class="p-6">
                        <div class="flex items-center space-x-4">
                            <!-- Provider Logo -->
                            <div class="w-16 h-16 flex-shrink-0 bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center overflow-hidden">
                                @if($provider['logo'])
                                    <img src="{{ $provider['logo'] }}" alt="{{ $provider['name'] }}" class="w-12 h-12 object-contain">
                                @else
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                                    </svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                                    {{ $provider['name'] }}
                                </h3>
                                @php
                                    $template = $provider['templates']['outbound'] ?? $provider['templates']['inbound'] ?? null;
                                @endphp
                                @if($template && $template->description)
                                    <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2 mt-1">
                                        {{ Str::limit($template->description, 80) }}
                                    </p>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Features badges -->
                        <div class="mt-4 flex flex-wrap gap-2">
                            @if($template)
                                @if($template->regions)
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/>
                                        </svg>
                                        {{ count($template->regions) }} Regions
                                    </span>
                                @endif
                                @if($template->hasMultipleAuthTypes())
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                        Multiple Auth Types
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>
                    
                    <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Click to configure</span>
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-primary-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Setup Modal -->
        <div x-cloak x-show="showModal" 
             class="fixed inset-0 z-50 overflow-y-auto" 
             aria-labelledby="modal-title" 
             role="dialog" 
             aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div x-show="showModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity"
                     @click="closeModal()"></div>

                <!-- Modal panel -->
                <div x-show="showModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="'Configure ' + (selectedTemplate?.provider_name || '')"></h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="direction === 'inbound' ? 'Inbound Trunk' : 'Outbound Trunk'"></p>
                            </div>
                        </div>
                        <button @click="closeModal()" class="text-gray-400 hover:text-gray-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <form @submit.prevent="submitForm()" class="px-6 py-4 space-y-4 max-h-[60vh] overflow-y-auto">
                        <!-- Carrier Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Carrier Name
                            </label>
                            <input type="text" x-model="formData.name" 
                                   class="form-input w-full"
                                   placeholder="e.g., twilio_outbound_1">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave empty to auto-generate</p>
                        </div>

                        <!-- Region Selection (if available) -->
                        <div x-show="selectedTemplate?.regions && Object.keys(selectedTemplate.regions).length > 0">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Region
                            </label>
                            <select x-model="formData.region" class="form-select w-full" @change="onRegionChange()">
                                <template x-for="(region, key) in selectedTemplate?.regions || {}" :key="key">
                                    <option :value="key" x-text="region.label"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Auth Type Toggle (if multiple) -->
                        <div x-show="selectedTemplate?.auth_types && selectedTemplate.auth_types.length > 1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Authentication Type
                            </label>
                            <div class="flex space-x-4">
                                <template x-for="authType in selectedTemplate?.auth_types || []" :key="authType">
                                    <label class="inline-flex items-center">
                                        <input type="radio" x-model="formData.auth_type" :value="authType" 
                                               class="form-radio text-primary-600">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300" 
                                              x-text="authType === 'credentials' ? 'Username/Password' : 'IP Authentication'"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <!-- Host (for generic templates) -->
                        <div x-show="!selectedTemplate?.regions || Object.keys(selectedTemplate.regions).length === 0">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                SIP Host <span class="text-red-500">*</span>
                            </label>
                            <input type="text" x-model="formData.host" 
                                   class="form-input w-full"
                                   placeholder="sip.provider.com">
                        </div>

                        <!-- Port -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Port
                                </label>
                                <input type="number" x-model="formData.port" 
                                       class="form-input w-full"
                                       placeholder="5060">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Transport
                                </label>
                                <select x-model="formData.transport" class="form-select w-full">
                                    <option value="udp">UDP</option>
                                    <option value="tcp">TCP</option>
                                    <option value="tls">TLS</option>
                                </select>
                            </div>
                        </div>

                        <!-- Credentials (show when auth_type is credentials or registration) -->
                        <div x-show="formData.auth_type === 'credentials' || (!selectedTemplate?.auth_types && selectedTemplate?.default_config?.auth_type === 'registration')">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Username <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" x-model="formData.username" 
                                           class="form-input w-full"
                                           placeholder="SIP username">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" 
                                       x-text="getFieldHelp('username')"></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Password <span class="text-red-500">*</span>
                                    </label>
                                    <input type="password" x-model="formData.password" 
                                           class="form-input w-full"
                                           placeholder="SIP password">
                                </div>
                            </div>
                            
                            <!-- From Domain (for providers that require it) -->
                            <div class="mt-4" x-show="isFieldRequired('from_domain')">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    SIP Domain <span class="text-red-500">*</span>
                                </label>
                                <input type="text" x-model="formData.from_domain" 
                                       class="form-input w-full"
                                       placeholder="sip.provider.com">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    The domain to use in SIP From header (usually provided by your carrier)
                                </p>
                            </div>
                        </div>

                        <!-- Provider-specific fields -->
                        <template x-for="field in selectedTemplate?.provider_fields || []" :key="field">
                            <div x-show="shouldShowProviderField(field)">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <span x-text="formatFieldName(field)"></span>
                                    <span x-show="isFieldRequired(field)" class="text-red-500">*</span>
                                </label>
                                <input type="text" x-model="formData[field]" 
                                       class="form-input w-full"
                                       :placeholder="getFieldPlaceholder(field)">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" 
                                   x-text="getFieldHelp(field)"></p>
                            </div>
                        </template>

                        <!-- Help Links -->
                        <div x-show="selectedTemplate?.help_links" class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Need help?</p>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="(url, label) in selectedTemplate?.help_links || {}" :key="label">
                                    <a :href="url" target="_blank" 
                                       class="inline-flex items-center text-sm text-primary-600 dark:text-primary-400 hover:underline">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                        <span x-text="formatFieldName(label)"></span>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </form>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <span x-show="errors.length > 0" class="text-sm text-red-600 dark:text-red-400" x-text="errors[0]"></span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button type="button" @click="closeModal()" class="btn-secondary">
                                Cancel
                            </button>
                            <button type="button" @click="submitForm()" 
                                    class="btn-primary"
                                    :disabled="submitting">
                                <svg x-show="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="submitting ? 'Creating...' : 'Create Carrier'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function quickSetup() {
            return {
                direction: 'outbound',
                showModal: false,
                selectedProvider: null,
                selectedTemplate: null,
                templates: @json($templates),
                formData: {},
                errors: [],
                submitting: false,

                init() {
                    // Set initial direction from URL if present
                    const urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.get('direction')) {
                        this.direction = urlParams.get('direction');
                    }
                },

                hasTemplate(providerSlug, direction) {
                    const provider = this.templates[providerSlug];
                    const result = provider && provider.templates && provider.templates[direction];
                    return result;
                },

                openSetupModal(providerSlug, direction) {
                    this.selectedProvider = providerSlug;
                    this.selectedTemplate = this.templates[providerSlug]?.templates[direction] || null;
                    
                    if (!this.selectedTemplate) {
                        console.error('Template not found');
                        return;
                    }

                    // Initialize form data from template defaults
                    const defaults = this.selectedTemplate.default_config || {};
                    this.formData = {
                        name: '',
                        region: Object.keys(this.selectedTemplate.regions || {})[0] || '',
                        auth_type: this.selectedTemplate.auth_types?.[0] || (defaults.auth_type === 'registration' ? 'credentials' : 'ip'),
                        host: defaults.host || '',
                        port: defaults.port || 5060,
                        transport: defaults.transport || 'udp',
                        username: '',
                        password: '',
                        from_domain: defaults.from_domain || '',
                    };

                    // Initialize provider-specific fields
                    (this.selectedTemplate.provider_fields || []).forEach(field => {
                        this.formData[field] = defaults[field] || '';
                    });

                    this.errors = [];
                    this.showModal = true;
                },

                closeModal() {
                    this.showModal = false;
                    this.selectedTemplate = null;
                    this.selectedProvider = null;
                    this.formData = {};
                    this.errors = [];
                },

                onRegionChange() {
                    if (this.selectedTemplate?.regions && this.formData.region) {
                        const region = this.selectedTemplate.regions[this.formData.region];
                        if (region) {
                            this.formData.host = region.host;
                            if (region.outbound_proxy) {
                                this.formData.outbound_proxy = region.outbound_proxy;
                            }
                        }
                    }
                },

                shouldShowProviderField(field) {
                    // Hide outbound_proxy if region has it
                    if (field === 'outbound_proxy' && this.selectedTemplate?.regions) {
                        const region = this.selectedTemplate.regions[this.formData.region];
                        return !region?.outbound_proxy;
                    }
                    return true;
                },

                isFieldRequired(field) {
                    const required = this.selectedTemplate?.required_fields;
                    if (!required) return false;

                    // Check if required_fields is keyed by auth type
                    if (typeof required === 'object' && !Array.isArray(required)) {
                        const authType = this.formData.auth_type;
                        return required[authType]?.includes(field);
                    }

                    return Array.isArray(required) && required.includes(field);
                },

                formatFieldName(field) {
                    return field.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
                },

                getFieldPlaceholder(field) {
                    const placeholders = {
                        trunk_sid: 'TK...',
                        credential_list_sid: 'CL...',
                        connection_id: 'Your Connection ID',
                        authorization_id: 'Authorization ID',
                        outbound_proxy: 'sip10.provider.com:5090',
                        api_key: 'Your API Key',
                        from_domain: 'sip.provider.com',
                    };
                    return placeholders[field] || '';
                },

                getFieldHelp(field) {
                    const help = {
                        username: 'Your SIP username or account ID',
                        password: 'Your SIP password or secret',
                        trunk_sid: 'Found in your Twilio Console under SIP Trunks',
                        credential_list_sid: 'Found in your Twilio Console under Credential Lists',
                        connection_id: 'Found in your Telnyx Portal under Connections',
                        authorization_id: 'May be the same as your username',
                        outbound_proxy: 'Required for proper call routing',
                        api_key: 'Found in your provider dashboard',
                        from_domain: 'The SIP domain provided by your carrier for authentication',
                    };
                    return help[field] || '';
                },

                async submitForm() {
                    this.errors = [];
                    this.submitting = true;

                    try {
                        const response = await fetch('{{ route("carriers.quick-setup.store") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                provider_slug: this.selectedProvider,
                                direction: this.direction,
                                ...this.formData,
                            }),
                        });

                        const data = await response.json();

                        if (data.success) {
                            window.location.href = data.redirect || '{{ route("carriers.index") }}';
                        } else {
                            this.errors = data.errors ? Object.values(data.errors).flat() : [data.message || 'Failed to create carrier'];
                        }
                    } catch (e) {
                        this.errors = ['An error occurred. Please try again.'];
                        console.error(e);
                    } finally {
                        this.submitting = false;
                    }
                }
            }
        }
    </script>
</x-app-layout>

