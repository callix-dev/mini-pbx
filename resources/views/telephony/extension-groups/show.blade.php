<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('extension-groups.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200 flex items-center">
                        {{ $extensionGroup->name }}
                        <span class="ml-3 badge {{ $extensionGroup->is_active ? 'badge-success' : 'badge-gray' }}">
                            {{ $extensionGroup->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $extensionGroup->description ?: 'No description' }}</p>
                </div>
            </div>
            <a href="{{ route('extension-groups.edit', $extensionGroup) }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit Group
            </a>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Group Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Group Settings</h3>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ring Strategy</dt>
                            <dd class="mt-1">
                                <span class="badge badge-primary">
                                    {{ $extensionGroup->ring_strategy_label }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ring Timeout</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $extensionGroup->ring_time }} seconds</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Members</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $extensionGroup->extensions->count() }} extensions</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $extensionGroup->created_at->format('M d, Y') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Members -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Group Members</h3>
                    <span class="badge badge-gray">{{ $extensionGroup->extensions->count() }} members</span>
                </div>
                <div class="p-6">
                    @if($extensionGroup->extensions->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($extensionGroup->extensions as $extension)
                                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-primary-600 rounded-full flex items-center justify-center text-white font-medium">
                                            {{ substr($extension->name, 0, 2) }}
                                        </div>
                                        <div>
                                            <a href="{{ route('extensions.show', $extension) }}" class="font-medium text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400">
                                                {{ $extension->extension }}
                                            </a>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $extension->name }}</p>
                                        </div>
                                    </div>
                                    @php
                                        $statusColors = [
                                            'online' => 'bg-green-500',
                                            'offline' => 'bg-gray-400',
                                            'ringing' => 'bg-yellow-500',
                                            'on_call' => 'bg-red-500',
                                        ];
                                    @endphp
                                    <span class="w-3 h-3 rounded-full {{ $statusColors[$extension->status] ?? 'bg-gray-400' }}" title="{{ ucfirst($extension->status) }}"></span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-gray-500 dark:text-gray-400 py-4">No extensions in this group.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Stats -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Member Status</h3>
                </div>
                <div class="p-6">
                    @php
                        $online = $extensionGroup->extensions->where('status', 'online')->count();
                        $onCall = $extensionGroup->extensions->where('status', 'on_call')->count();
                        $offline = $extensionGroup->extensions->where('status', 'offline')->count();
                    @endphp
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                                <span class="text-sm text-gray-600 dark:text-gray-400">Online</span>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $online }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="w-3 h-3 bg-red-500 rounded-full mr-2"></span>
                                <span class="text-sm text-gray-600 dark:text-gray-400">On Call</span>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $onCall }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="w-3 h-3 bg-gray-400 rounded-full mr-2"></span>
                                <span class="text-sm text-gray-600 dark:text-gray-400">Offline</span>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $offline }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Used In -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Used In</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @if($extensionGroup->dids && $extensionGroup->dids->count() > 0)
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">DIDs</p>
                                @foreach($extensionGroup->dids as $did)
                                    <a href="{{ route('dids.show', $did) }}" class="block text-sm text-primary-600 dark:text-primary-400 hover:underline">
                                        {{ $did->did_number }}
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        @if($extensionGroup->ringTrees && $extensionGroup->ringTrees->count() > 0)
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">Ring Trees</p>
                                @foreach($extensionGroup->ringTrees as $ringTree)
                                    <a href="{{ route('ring-trees.show', $ringTree) }}" class="block text-sm text-primary-600 dark:text-primary-400 hover:underline">
                                        {{ $ringTree->name }}
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        @if((!$extensionGroup->dids || $extensionGroup->dids->count() === 0) && (!$extensionGroup->ringTrees || $extensionGroup->ringTrees->count() === 0))
                            <p class="text-sm text-gray-500 dark:text-gray-400">Not used in any routing.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

