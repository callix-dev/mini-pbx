<x-app-layout>
    @section('title', 'Extensions')
    @section('page-title', 'Extensions')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Extensions</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage PJSIP extensions for your PBX system
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('extensions.create') }}" class="btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    New Extension
                </a>
                <button type="button" onclick="openBulkCreateModal()" class="btn-secondary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Bulk Create
                </button>
                <button type="button" onclick="document.getElementById('bulk-upload').click()" class="btn-secondary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Import CSV
                </button>
                <form id="bulk-upload-form" action="{{ route('extensions.bulk-create') }}" method="POST" enctype="multipart/form-data" class="hidden">
                    @csrf
                    <input type="file" id="bulk-upload" name="file" accept=".csv,.xlsx,.xls" onchange="this.form.submit()">
                </form>
            </div>
        </div>
    </x-slot>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Search extensions..."
                       class="form-input">
            </div>
            <div class="w-40">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="online" {{ request('status') === 'online' ? 'selected' : '' }}>Online</option>
                    <option value="offline" {{ request('status') === 'offline' ? 'selected' : '' }}>Offline</option>
                    <option value="ringing" {{ request('status') === 'ringing' ? 'selected' : '' }}>Ringing</option>
                    <option value="on_call" {{ request('status') === 'on_call' ? 'selected' : '' }}>On Call</option>
                </select>
            </div>
            <div class="w-32">
                <select name="active" class="form-select">
                    <option value="">All</option>
                    <option value="yes" {{ request('active') === 'yes' ? 'selected' : '' }}>Active</option>
                    <option value="no" {{ request('active') === 'no' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn-primary">Filter</button>
            @if(request()->hasAny(['search', 'status', 'active']))
                <a href="{{ route('extensions.index') }}" class="btn-secondary">Clear</a>
            @endif
        </form>
    </div>

    <!-- Extensions Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th class="w-10">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                        </th>
                        <th class="w-24">Extension</th>
                        <th>Name / User</th>
                        <th class="w-24 text-center">Status</th>
                        <th class="w-20 text-center">VM</th>
                        <th>Groups</th>
                        <th class="w-36">Last Registered</th>
                        <th class="w-20 text-center">Active</th>
                        <th class="w-28 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($extensions as $extension)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td>
                                <input type="checkbox" name="ids[]" value="{{ $extension->id }}" 
                                       class="extension-checkbox rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                            </td>
                            <td>
                                <a href="{{ route('extensions.show', $extension) }}" class="font-mono font-bold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 hover:underline">
                                    {{ $extension->extension }}
                                </a>
                            </td>
                            <td>
                                <div class="flex items-center">
                                    <div class="relative flex-shrink-0">
                                        <div class="w-9 h-9 bg-gradient-to-br from-primary-400 to-accent-500 rounded-lg flex items-center justify-center shadow-sm">
                                            <span class="text-xs font-bold text-white">
                                                {{ strtoupper(substr($extension->name, 0, 2)) }}
                                            </span>
                                        </div>
                                        <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white dark:border-gray-800 
                                            {{ $extension->status === 'online' ? 'bg-green-500' : ($extension->status === 'on_call' ? 'bg-red-500' : ($extension->status === 'ringing' ? 'bg-yellow-500' : 'bg-gray-400')) }}">
                                        </span>
                                    </div>
                                    <div class="ml-3 min-w-0">
                                        <p class="font-medium text-gray-900 dark:text-white truncate">{{ $extension->name }}</p>
                                        @if($extension->user)
                                            <a href="{{ route('users.show', $extension->user) }}" 
                                               class="text-xs text-primary-600 dark:text-primary-400 hover:underline flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                                {{ $extension->user->name }}
                                            </a>
                                        @else
                                            <span class="text-xs text-gray-400 dark:text-gray-500">No user assigned</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                @php
                                    $statusConfig = [
                                        'online' => ['class' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400', 'icon' => 'M5 13l4 4L19 7'],
                                        'offline' => ['class' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400', 'icon' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
                                        'ringing' => ['class' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400', 'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
                                        'on_call' => ['class' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400', 'icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'],
                                    ];
                                    $config = $statusConfig[$extension->status] ?? $statusConfig['offline'];
                                @endphp
                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium {{ $config['class'] }}">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $config['icon'] }}"/>
                                    </svg>
                                    {{ ucfirst(str_replace('_', ' ', $extension->status)) }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($extension->voicemail_enabled)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-green-100 dark:bg-green-900/30" title="Voicemail Enabled">
                                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-700" title="Voicemail Disabled">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($extension->groups->count() > 0)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($extension->groups->take(2) as $group)
                                            <a href="{{ route('extension-groups.show', $group) }}" 
                                               class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400 hover:bg-primary-200 dark:hover:bg-primary-900/50">
                                                {{ $group->name }}
                                            </a>
                                        @endforeach
                                        @if($extension->groups->count() > 2)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                +{{ $extension->groups->count() - 2 }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">—</span>
                                @endif
                            </td>
                            <td>
                                @if($extension->last_registered_at)
                                    <div class="text-xs">
                                        <p class="text-gray-900 dark:text-white">{{ $extension->last_registered_at->diffForHumans() }}</p>
                                        <p class="text-gray-500 dark:text-gray-400 font-mono">{{ $extension->last_registered_ip ?? 'N/A' }}</p>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">Never</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <form action="{{ route('extensions.toggle-status', $extension) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $extension->is_active ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}" title="{{ $extension->is_active ? 'Click to disable' : 'Click to enable' }}">
                                        <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $extension->is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                                    </button>
                                </form>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end space-x-1">
                                    <a href="{{ route('extensions.show', $extension) }}" 
                                       class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                                       title="View Details">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('extensions.edit', $extension) }}" 
                                       class="p-1.5 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                                       title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('extensions.destroy', $extension) }}" method="POST" class="inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this extension?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                                                title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No extensions found</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new extension.</p>
                                <div class="mt-6 flex justify-center gap-3">
                                    <a href="{{ route('extensions.create') }}" class="btn-primary">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                        New Extension
                                    </a>
                                    <button type="button" onclick="openBulkCreateModal()" class="btn-secondary">Bulk Create</button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($extensions->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $extensions->withQueryString()->links() }}
            </div>
        @endif
    </div>

    <!-- Bulk Actions Bar -->
    <div id="bulk-actions" class="hidden fixed bottom-6 left-1/2 transform -translate-x-1/2 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 px-6 py-4 z-50">
        <form action="{{ route('extensions.bulk-action') }}" method="POST" id="bulk-action-form" class="flex items-center gap-4">
            @csrf
            <div class="flex items-center gap-2">
                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 font-bold text-sm" id="selected-count">0</span>
                <span class="text-sm text-gray-600 dark:text-gray-400">selected</span>
            </div>
            
            <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>
            
            <input type="hidden" name="ids" id="bulk-ids">
            <input type="hidden" name="password_type" id="password-type-input">
            <input type="hidden" name="fixed_password" id="fixed-password-input">
            
            <select name="action" id="bulk-action-select" class="form-select text-sm py-2" onchange="handleActionChange(this.value)">
                <option value="">Select Action...</option>
                <optgroup label="Status">
                    <option value="enable">✓ Enable</option>
                    <option value="disable">✗ Disable</option>
                </optgroup>
                <optgroup label="Password">
                    <option value="change_password_random">🔑 Random Password</option>
                    <option value="change_password_fixed">🔐 Set Fixed Password</option>
                    <option value="change_password_extension">📱 Password = Extension</option>
                </optgroup>
                <optgroup label="Danger">
                    <option value="delete">🗑️ Delete</option>
                </optgroup>
            </select>
            
            <div id="fixed-password-container" class="hidden">
                <input type="password" id="fixed-password" placeholder="Enter password" class="form-input text-sm py-2 w-40">
            </div>
            
            <button type="submit" class="btn-primary text-sm py-2" id="apply-bulk-btn" disabled>
                Apply
            </button>
            
            <button type="button" onclick="clearSelection()" class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </form>
    </div>

    <!-- Bulk Create Modal -->
    <div id="bulk-create-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity" onclick="closeBulkCreateModal()"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="{{ route('extensions.bulk-create-range') }}" method="POST">
                    @csrf
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Bulk Create Extensions</h3>
                            <button type="button" onclick="closeBulkCreateModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="px-6 py-5 space-y-5">
                        <!-- Extension Range -->
                        <div>
                            <label class="form-label">Extension Range <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <input type="number" name="start_extension" id="start_extension" 
                                           class="form-input" placeholder="Start (e.g., 100)" required min="1" max="99999">
                                    <p class="mt-1 text-xs text-gray-500">First extension number</p>
                                </div>
                                <div>
                                    <input type="number" name="end_extension" id="end_extension" 
                                           class="form-input" placeholder="End (e.g., 199)" required min="1" max="99999">
                                    <p class="mt-1 text-xs text-gray-500">Last extension number</p>
                                </div>
                            </div>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400" id="extension-count-preview">
                                This will create <strong>0</strong> extensions
                            </p>
                        </div>

                        <!-- Name Template -->
                        <div>
                            <label for="name_template" class="form-label">Name Template <span class="text-red-500">*</span></label>
                            <input type="text" name="name_template" id="name_template" 
                                   class="form-input" placeholder="Extension {ext}" value="Extension {ext}" required>
                            <p class="mt-1 text-xs text-gray-500">Use <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">{ext}</code> as placeholder for extension number</p>
                        </div>

                        <!-- Password Type -->
                        <div>
                            <label class="form-label">Password Type <span class="text-red-500">*</span></label>
                            <div class="space-y-3 mt-2">
                                <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="radio" name="password_type" value="random" checked 
                                           class="text-primary-600 focus:ring-primary-500" onchange="togglePasswordInput()">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900 dark:text-white">Random Password</span>
                                        <p class="text-xs text-gray-500">Generate unique secure password for each extension</p>
                                    </div>
                                </label>
                                <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="radio" name="password_type" value="same_as_extension" 
                                           class="text-primary-600 focus:ring-primary-500" onchange="togglePasswordInput()">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900 dark:text-white">Same as Extension</span>
                                        <p class="text-xs text-gray-500">Password will be the extension number (not recommended)</p>
                                    </div>
                                </label>
                                <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                    <input type="radio" name="password_type" value="fixed" 
                                           class="text-primary-600 focus:ring-primary-500" onchange="togglePasswordInput()">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900 dark:text-white">Fixed Password</span>
                                        <p class="text-xs text-gray-500">Use the same password for all extensions</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Fixed Password Input -->
                        <div id="bulk-fixed-password-container" class="hidden">
                            <label for="bulk_fixed_password" class="form-label">Password <span class="text-red-500">*</span></label>
                            <input type="password" name="fixed_password" id="bulk_fixed_password" 
                                   class="form-input" placeholder="Enter password" minlength="6">
                            <p class="mt-1 text-xs text-gray-500">Minimum 6 characters</p>
                        </div>

                        <!-- Options -->
                        <div class="space-y-3">
                            <label class="flex items-center">
                                <input type="checkbox" name="voicemail_enabled" value="1" 
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <span class="ml-2 text-gray-700 dark:text-gray-300">Enable Voicemail</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" value="1" checked
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <span class="ml-2 text-gray-700 dark:text-gray-300">Set as Active</span>
                            </label>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex items-center justify-end gap-3">
                        <button type="button" onclick="closeBulkCreateModal()" class="btn-secondary">Cancel</button>
                        <button type="submit" class="btn-primary">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Create Extensions
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.extension-checkbox');
            const bulkActions = document.getElementById('bulk-actions');
            const selectedCount = document.getElementById('selected-count');
            const bulkIds = document.getElementById('bulk-ids');
            const applyBtn = document.getElementById('apply-bulk-btn');
            const actionSelect = document.getElementById('bulk-action-select');

            function updateBulkActions() {
                const checked = document.querySelectorAll('.extension-checkbox:checked');
                if (checked.length > 0) {
                    bulkActions.classList.remove('hidden');
                    selectedCount.textContent = checked.length;
                    bulkIds.value = Array.from(checked).map(cb => cb.value).join(',');
                } else {
                    bulkActions.classList.add('hidden');
                }
            }

            selectAll?.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                updateBulkActions();
            });

            checkboxes.forEach(cb => {
                cb.addEventListener('change', updateBulkActions);
            });

            window.clearSelection = function() {
                selectAll.checked = false;
                checkboxes.forEach(cb => cb.checked = false);
                actionSelect.value = '';
                document.getElementById('fixed-password-container').classList.add('hidden');
                applyBtn.disabled = true;
                updateBulkActions();
            };

            // Extension range preview
            const startExt = document.getElementById('start_extension');
            const endExt = document.getElementById('end_extension');
            const preview = document.getElementById('extension-count-preview');

            function updatePreview() {
                const start = parseInt(startExt.value) || 0;
                const end = parseInt(endExt.value) || 0;
                const count = end >= start ? end - start + 1 : 0;
                preview.innerHTML = `This will create <strong>${count}</strong> extensions`;
            }

            startExt?.addEventListener('input', updatePreview);
            endExt?.addEventListener('input', updatePreview);
        });

        function handleActionChange(action) {
            const fixedContainer = document.getElementById('fixed-password-container');
            const fixedPassword = document.getElementById('fixed-password');
            const applyBtn = document.getElementById('apply-bulk-btn');
            const passwordTypeInput = document.getElementById('password-type-input');
            const fixedPasswordInput = document.getElementById('fixed-password-input');

            if (action === 'change_password_fixed') {
                fixedContainer.classList.remove('hidden');
                fixedPassword.required = true;
                passwordTypeInput.value = 'fixed';
            } else {
                fixedContainer.classList.add('hidden');
                fixedPassword.required = false;
                fixedPassword.value = '';
                
                if (action === 'change_password_random') {
                    passwordTypeInput.value = 'random';
                } else if (action === 'change_password_extension') {
                    passwordTypeInput.value = 'same_as_extension';
                }
            }

            applyBtn.disabled = !action;

            // Confirmation for dangerous actions
            if (action === 'delete') {
                document.getElementById('bulk-action-form').onsubmit = function() {
                    return confirm('Are you sure you want to DELETE the selected extensions? This cannot be undone!');
                };
            } else {
                document.getElementById('bulk-action-form').onsubmit = null;
            }

            // Update fixed password for submission
            if (fixedPassword) {
                fixedPassword.addEventListener('input', function() {
                    fixedPasswordInput.value = this.value;
                });
            }
        }

        function togglePasswordInput() {
            const fixedContainer = document.getElementById('bulk-fixed-password-container');
            const fixedRadio = document.querySelector('input[name="password_type"][value="fixed"]');
            const fixedPassword = document.getElementById('bulk_fixed_password');

            if (fixedRadio.checked) {
                fixedContainer.classList.remove('hidden');
                fixedPassword.required = true;
            } else {
                fixedContainer.classList.add('hidden');
                fixedPassword.required = false;
            }
        }

        function openBulkCreateModal() {
            document.getElementById('bulk-create-modal').classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeBulkCreateModal() {
            document.getElementById('bulk-create-modal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeBulkCreateModal();
            }
        });
    </script>
</x-app-layout>
