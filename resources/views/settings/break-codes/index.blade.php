<x-app-layout>
    @section('title', 'Break Codes')
    @section('page-title', 'Break Codes')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Break Codes</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage agent break and pause reasons
                </p>
            </div>
            <a href="{{ route('break-codes.create') }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                New Break Code
            </a>
        </div>
    </x-slot>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Color</th>
                        <th>Max Duration</th>
                        <th>Paid</th>
                        <th>Active</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($breakCodes ?? [] as $breakCode)
                        <tr>
                            <td>
                                <div class="flex items-center">
                                    <span class="w-3 h-3 rounded-full mr-2" style="background-color: {{ $breakCode->color ?? '#6B7280' }}"></span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $breakCode->name }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="text-gray-500 dark:text-gray-400">{{ $breakCode->description ?: '-' }}</span>
                            </td>
                            <td>
                                <span class="font-mono text-sm text-gray-500 dark:text-gray-400">{{ $breakCode->color ?? '#6B7280' }}</span>
                            </td>
                            <td>
                                @if($breakCode->max_duration)
                                    <span class="text-gray-900 dark:text-white">{{ $breakCode->max_duration }} min</span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">No limit</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $breakCode->is_paid ? 'badge-success' : 'badge-gray' }}">
                                    {{ $breakCode->is_paid ? 'Paid' : 'Unpaid' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $breakCode->is_active ? 'badge-success' : 'badge-gray' }}">
                                    {{ $breakCode->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('break-codes.edit', $breakCode) }}" 
                                       class="p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                       title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('break-codes.destroy', $breakCode) }}" method="POST" class="inline" 
                                          onsubmit="return confirm('Are you sure?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                                title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No break codes</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a break code.</p>
                                <div class="mt-6">
                                    <a href="{{ route('break-codes.create') }}" class="btn-primary">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                        New Break Code
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>

