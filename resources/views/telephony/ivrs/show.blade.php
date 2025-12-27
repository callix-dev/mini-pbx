<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('ivrs.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    IVR: {{ $ivr->name ?? 'Unknown' }}
                </h2>
                @if($ivr->is_active ?? false)
                    <span class="badge badge-success">Active</span>
                @else
                    <span class="badge badge-danger">Inactive</span>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('ivrs.edit', $ivr ?? 0) }}" class="btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <!-- Basic Information -->
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="text-lg font-medium">Basic Information</h3>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</dt>
                            <dd class="mt-1 text-gray-900 dark:text-white">{{ $ivr->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                            <dd class="mt-1">
                                @if($ivr->is_active ?? false)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </dd>
                        </div>
                        <div class="md:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
                            <dd class="mt-1 text-gray-900 dark:text-white">{{ $ivr->description ?? 'No description' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Greeting Audio</dt>
                            <dd class="mt-1 text-gray-900 dark:text-white">{{ $ivr->greeting->name ?? 'None' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Timeout</dt>
                            <dd class="mt-1 text-gray-900 dark:text-white">{{ $ivr->timeout ?? 10 }} seconds</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Menu Options -->
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="text-lg font-medium">Menu Options</h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @php $options = $ivr->options ?? []; @endphp
                        @foreach(['1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '*', '#'] as $key)
                            <div class="p-4 rounded-lg {{ isset($options[$key]) && !empty($options[$key]['action']) ? 'bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800' : 'bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700' }}">
                                <div class="text-center">
                                    <span class="text-2xl font-bold {{ isset($options[$key]) && !empty($options[$key]['action']) ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400' }}">{{ $key }}</span>
                                    <div class="mt-2 text-sm">
                                        @if(isset($options[$key]) && !empty($options[$key]['action']))
                                            <span class="text-primary-700 dark:text-primary-300">{{ ucfirst(str_replace('_', ' ', $options[$key]['action'])) }}</span>
                                            @if(!empty($options[$key]['destination']))
                                                <br><span class="text-gray-600 dark:text-gray-400">{{ $options[$key]['destination'] }}</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">Disabled</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Fallback Actions -->
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="text-lg font-medium">Fallback Actions</h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                            <h4 class="font-medium text-amber-800 dark:text-amber-200 mb-2">On Invalid Input</h4>
                            <p class="text-amber-700 dark:text-amber-300">
                                {{ ucfirst($ivr->invalid_action ?? 'repeat') }}
                                @if($ivr->invalid_destination ?? null)
                                    → {{ $ivr->invalid_destination }}
                                @endif
                            </p>
                            <p class="text-sm text-amber-600 dark:text-amber-400 mt-1">
                                After {{ $ivr->invalid_retries ?? 3 }} retries
                            </p>
                        </div>
                        
                        <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                            <h4 class="font-medium text-red-800 dark:text-red-200 mb-2">On Timeout</h4>
                            <p class="text-red-700 dark:text-red-300">
                                {{ ucfirst($ivr->timeout_action ?? 'repeat') }}
                                @if($ivr->timeout_destination ?? null)
                                    → {{ $ivr->timeout_destination }}
                                @endif
                            </p>
                            <p class="text-sm text-red-600 dark:text-red-400 mt-1">
                                After {{ $ivr->timeout_retries ?? 3 }} retries
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usage -->
            <div class="card">
                <div class="card-header">
                    <h3 class="text-lg font-medium">Usage</h3>
                </div>
                <div class="card-body">
                    @if(($ivr->dids ?? collect())->count() > 0)
                        <div class="space-y-2">
                            @foreach($ivr->dids as $did)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <div>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $did->did_number }}</span>
                                        <span class="text-gray-500 dark:text-gray-400 ml-2">{{ $did->description }}</span>
                                    </div>
                                    <a href="{{ route('dids.show', $did) }}" class="text-primary-600 hover:text-primary-800 dark:text-primary-400">
                                        View DID
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p>This IVR is not assigned to any DIDs yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>



