<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('ring-trees.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Ring Tree: {{ $ringTree->name ?? 'Unknown' }}
                </h2>
                @if($ringTree->is_active ?? false)
                    <span class="badge badge-success">Active</span>
                @else
                    <span class="badge badge-danger">Inactive</span>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('ring-trees.edit', $ringTree ?? 0) }}" class="btn-primary">
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
                            <dd class="mt-1 text-gray-900 dark:text-white">{{ $ringTree->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                            <dd class="mt-1">
                                @if($ringTree->is_active ?? false)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </dd>
                        </div>
                        <div class="md:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
                            <dd class="mt-1 text-gray-900 dark:text-white">{{ $ringTree->description ?? 'No description' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
                            <dd class="mt-1 text-gray-900 dark:text-white">{{ $ringTree->created_at?->format('M d, Y H:i') ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</dt>
                            <dd class="mt-1 text-gray-900 dark:text-white">{{ $ringTree->updated_at?->format('M d, Y H:i') ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Ring Tree Flow -->
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="text-lg font-medium">Ring Sequence</h3>
                </div>
                <div class="card-body">
                    <div class="space-y-4">
                        @forelse($ringTree->nodes ?? [] as $index => $node)
                            <div class="flex items-center gap-4">
                                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                    <span class="text-primary-600 dark:text-primary-400 font-bold">{{ $index + 1 }}</span>
                                </div>
                                <div class="flex-1 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="font-medium text-gray-900 dark:text-white">
                                                {{ ucfirst(str_replace('_', ' ', $node->destination_type ?? 'Unknown')) }}
                                            </span>
                                            <span class="text-gray-600 dark:text-gray-400">
                                                → {{ $node->destination?->name ?? $node->destination_id ?? 'Not configured' }}
                                            </span>
                                        </div>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $node->ring_time ?? 20 }} seconds
                                        </span>
                                    </div>
                                </div>
                                @if(!$loop->last)
                                    <div class="flex-shrink-0">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                No ring tree nodes configured
                            </div>
                        @endforelse

                        <!-- Final Destination -->
                        <div class="flex items-center gap-4 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900 flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div class="flex-1 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                                <span class="font-medium text-amber-800 dark:text-amber-200">Final Destination:</span>
                                <span class="text-amber-700 dark:text-amber-300 ml-2">
                                    {{ ucfirst($ringTree->final_destination_type ?? 'Voicemail') }}
                                    @if($ringTree->final_destination_value ?? null)
                                        → {{ $ringTree->final_destination_value }}
                                    @endif
                                </span>
                            </div>
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
                    @if(($ringTree->dids ?? collect())->count() > 0)
                        <div class="space-y-2">
                            @foreach($ringTree->dids as $did)
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
                            <p>This ring tree is not assigned to any DIDs yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>



