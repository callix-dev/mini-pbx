<x-app-layout>
    @section('title', 'Carriers')
    @section('page-title', 'Carriers')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Carriers</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage outbound and inbound SIP carriers
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('carriers.quick-setup') }}" class="btn-secondary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Quick Setup
                </a>
                <a href="{{ route('carriers.create') }}" class="btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    New Carrier
                </a>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse($carriers ?? [] as $carrier)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            @if($carrier->provider_slug)
                                <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center overflow-hidden">
                                    <img src="{{ asset('images/carriers/' . $carrier->provider_slug . '.svg') }}" alt="{{ $carrier->provider_name }}" class="w-8 h-8 object-contain">
                                </div>
                            @else
                                <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                    </svg>
                                </div>
                            @endif
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $carrier->name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $carrier->host }}:{{ $carrier->port }}
                                    @if($carrier->provider_name)
                                        <span class="text-xs ml-2 px-1.5 py-0.5 bg-gray-100 dark:bg-gray-600 rounded">{{ $carrier->provider_name }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <span class="badge {{ $carrier->is_active ? 'badge-success' : 'badge-gray' }}">
                            {{ $carrier->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Type:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ ucfirst($carrier->type) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Protocol:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ strtoupper($carrier->transport) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Username:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $carrier->username ?: '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Priority:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $carrier->priority }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end space-x-2">
                    <button type="button" 
                            onclick="testConnection({{ $carrier->id }})"
                            class="btn-secondary text-sm py-1.5 px-3">
                        <span class="test-btn-text">Test</span>
                        <span class="test-btn-loading hidden">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                    <form action="{{ route('carriers.toggle-status', $carrier) }}" method="POST" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn-secondary text-sm py-1.5 px-3">
                            {{ $carrier->is_active ? 'Disable' : 'Enable' }}
                        </button>
                    </form>
                    <a href="{{ route('carriers.edit', $carrier) }}" class="btn-secondary text-sm py-1.5 px-3">Edit</a>
                    <form action="{{ route('carriers.destroy', $carrier) }}" method="POST" class="inline" 
                          onsubmit="return confirm('Are you sure?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger text-sm py-1.5 px-3">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No carriers configured</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add carriers to enable inbound and outbound calling.</p>
                    <div class="mt-6">
                        <a href="{{ route('carriers.create') }}" class="btn-primary">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            New Carrier
                        </a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Test Result Modal -->
    <div id="testResultModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity" onclick="closeTestModal()"></div>
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Connection Test Results</h3>
                    <button onclick="closeTestModal()" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="px-6 py-4" id="testResultContent">
                    <!-- Results will be inserted here -->
                </div>
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                    <button onclick="closeTestModal()" class="btn-secondary">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function testConnection(carrierId) {
            const btn = event.target.closest('button');
            const textEl = btn.querySelector('.test-btn-text');
            const loadingEl = btn.querySelector('.test-btn-loading');
            
            textEl.classList.add('hidden');
            loadingEl.classList.remove('hidden');
            btn.disabled = true;

            try {
                const response = await fetch(`/settings/carriers/${carrierId}/test-connection`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();
                showTestResults(data);
            } catch (e) {
                showTestResults({
                    success: false,
                    message: 'Failed to test connection: ' + e.message,
                    details: {}
                });
            } finally {
                textEl.classList.remove('hidden');
                loadingEl.classList.add('hidden');
                btn.disabled = false;
            }
        }

        function showTestResults(data) {
            const modal = document.getElementById('testResultModal');
            const content = document.getElementById('testResultContent');

            let html = `
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center ${data.success ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30'}">
                        ${data.success 
                            ? '<svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>'
                            : '<svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>'
                        }
                    </div>
                    <div>
                        <h4 class="font-medium ${data.success ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'}">${data.success ? 'Success' : 'Failed'}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-300">${data.message}</p>
                    </div>
                </div>
            `;

            if (data.details && Object.keys(data.details).length > 0) {
                html += '<div class="space-y-2 border-t border-gray-200 dark:border-gray-700 pt-4">';
                for (const [key, detail] of Object.entries(data.details)) {
                    const isOk = detail.exists || detail.registered || detail.reachable;
                    html += `
                        <div class="flex items-center justify-between py-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">${formatKey(key)}</span>
                            <span class="text-sm ${isOk ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}">${detail.message || '-'}</span>
                        </div>
                    `;
                }
                html += '</div>';
            }

            content.innerHTML = html;
            modal.classList.remove('hidden');
        }

        function closeTestModal() {
            document.getElementById('testResultModal').classList.add('hidden');
        }

        function formatKey(key) {
            return key.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
        }
    </script>
</x-app-layout>







