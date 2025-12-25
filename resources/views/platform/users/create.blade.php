<x-app-layout>
    @section('title', 'Create User')
    @section('page-title', 'Create User')

    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('users.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Create New User</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add a new user to the system</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <form action="{{ route('users.store') }}" method="POST" class="space-y-6">
            @csrf

            @if ($errors->any())
                <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                    <div class="flex">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <h4 class="text-sm font-medium text-red-800 dark:text-red-200">Please fix the following errors:</h4>
                            <ul class="mt-2 text-sm text-red-700 dark:text-red-300 list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Basic Information -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Basic Information</h3>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="form-label">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" 
                                   class="form-input @error('name') border-red-500 @enderror" 
                                   placeholder="John Doe" required>
                            @error('name')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="form-label">Email Address <span class="text-red-500">*</span></label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" 
                                   class="form-input @error('email') border-red-500 @enderror" 
                                   placeholder="john@example.com" required>
                            @error('email')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="password" class="form-label">Password <span class="text-red-500">*</span></label>
                            <input type="password" name="password" id="password" 
                                   class="form-input @error('password') border-red-500 @enderror" 
                                   placeholder="••••••••" required minlength="8">
                            <p class="mt-1 text-xs text-gray-500">Minimum 8 characters</p>
                            @error('password')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="form-label">Confirm Password <span class="text-red-500">*</span></label>
                            <input type="password" name="password_confirmation" id="password_confirmation" 
                                   class="form-input" 
                                   placeholder="••••••••" required minlength="8">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" 
                                   class="form-input" 
                                   placeholder="+1 234 567 8900">
                        </div>

                        <div>
                            <label for="timezone" class="form-label">Timezone</label>
                            <select name="timezone" id="timezone" class="form-select">
                                @php
                                    $selectedTimezone = old('timezone', 'UTC');
                                    $timezones = [
                                        'UTC' => ['name' => 'UTC', 'offset' => '+00:00'],
                                        'Pacific/Midway' => ['name' => 'SST', 'offset' => '-11:00'],
                                        'Pacific/Honolulu' => ['name' => 'HST', 'offset' => '-10:00'],
                                        'America/Anchorage' => ['name' => 'AKST', 'offset' => '-09:00'],
                                        'America/Los_Angeles' => ['name' => 'PST', 'offset' => '-08:00'],
                                        'America/Denver' => ['name' => 'MST', 'offset' => '-07:00'],
                                        'America/Chicago' => ['name' => 'CST', 'offset' => '-06:00'],
                                        'America/New_York' => ['name' => 'EST', 'offset' => '-05:00'],
                                        'America/Caracas' => ['name' => 'VET', 'offset' => '-04:00'],
                                        'America/Halifax' => ['name' => 'AST', 'offset' => '-04:00'],
                                        'America/St_Johns' => ['name' => 'NST', 'offset' => '-03:30'],
                                        'America/Sao_Paulo' => ['name' => 'BRT', 'offset' => '-03:00'],
                                        'America/Argentina/Buenos_Aires' => ['name' => 'ART', 'offset' => '-03:00'],
                                        'Atlantic/South_Georgia' => ['name' => 'GST', 'offset' => '-02:00'],
                                        'Atlantic/Azores' => ['name' => 'AZOT', 'offset' => '-01:00'],
                                        'Europe/London' => ['name' => 'GMT', 'offset' => '+00:00'],
                                        'Europe/Paris' => ['name' => 'CET', 'offset' => '+01:00'],
                                        'Europe/Berlin' => ['name' => 'CET', 'offset' => '+01:00'],
                                        'Africa/Lagos' => ['name' => 'WAT', 'offset' => '+01:00'],
                                        'Africa/Cairo' => ['name' => 'EET', 'offset' => '+02:00'],
                                        'Africa/Johannesburg' => ['name' => 'SAST', 'offset' => '+02:00'],
                                        'Europe/Athens' => ['name' => 'EET', 'offset' => '+02:00'],
                                        'Asia/Jerusalem' => ['name' => 'IST', 'offset' => '+02:00'],
                                        'Europe/Moscow' => ['name' => 'MSK', 'offset' => '+03:00'],
                                        'Asia/Kuwait' => ['name' => 'AST', 'offset' => '+03:00'],
                                        'Africa/Nairobi' => ['name' => 'EAT', 'offset' => '+03:00'],
                                        'Asia/Tehran' => ['name' => 'IRST', 'offset' => '+03:30'],
                                        'Asia/Dubai' => ['name' => 'GST', 'offset' => '+04:00'],
                                        'Asia/Kabul' => ['name' => 'AFT', 'offset' => '+04:30'],
                                        'Asia/Karachi' => ['name' => 'PKT', 'offset' => '+05:00'],
                                        'Asia/Kolkata' => ['name' => 'IST', 'offset' => '+05:30'],
                                        'Asia/Kathmandu' => ['name' => 'NPT', 'offset' => '+05:45'],
                                        'Asia/Dhaka' => ['name' => 'BST', 'offset' => '+06:00'],
                                        'Asia/Yangon' => ['name' => 'MMT', 'offset' => '+06:30'],
                                        'Asia/Bangkok' => ['name' => 'ICT', 'offset' => '+07:00'],
                                        'Asia/Jakarta' => ['name' => 'WIB', 'offset' => '+07:00'],
                                        'Asia/Shanghai' => ['name' => 'CST', 'offset' => '+08:00'],
                                        'Asia/Singapore' => ['name' => 'SGT', 'offset' => '+08:00'],
                                        'Asia/Hong_Kong' => ['name' => 'HKT', 'offset' => '+08:00'],
                                        'Australia/Perth' => ['name' => 'AWST', 'offset' => '+08:00'],
                                        'Asia/Tokyo' => ['name' => 'JST', 'offset' => '+09:00'],
                                        'Asia/Seoul' => ['name' => 'KST', 'offset' => '+09:00'],
                                        'Australia/Adelaide' => ['name' => 'ACST', 'offset' => '+09:30'],
                                        'Australia/Sydney' => ['name' => 'AEST', 'offset' => '+10:00'],
                                        'Pacific/Guam' => ['name' => 'ChST', 'offset' => '+10:00'],
                                        'Pacific/Noumea' => ['name' => 'NCT', 'offset' => '+11:00'],
                                        'Pacific/Auckland' => ['name' => 'NZST', 'offset' => '+12:00'],
                                        'Pacific/Fiji' => ['name' => 'FJT', 'offset' => '+12:00'],
                                        'Pacific/Tongatapu' => ['name' => 'TOT', 'offset' => '+13:00'],
                                    ];
                                @endphp
                                @foreach($timezones as $tz => $info)
                                    <option value="{{ $tz }}" {{ $selectedTimezone === $tz ? 'selected' : '' }}>
                                        {{ $info['name'] }} (UTC{{ $info['offset'] }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Role & Assignment -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Role & Assignment</h3>
                </div>
                <div class="p-6 space-y-6">
                    <div>
                        <label class="form-label">Role <span class="text-red-500">*</span></label>
                        @error('role')
                            <p class="form-error mb-2">{{ $message }}</p>
                        @enderror
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                            @foreach($roles as $role)
                                @php
                                    $roleDescriptions = [
                                        'Superadmin' => 'Full system access with all permissions',
                                        'Admin' => 'Administrative access without system settings',
                                        'Manager' => 'Team management and reporting access',
                                        'Quality Analyst' => 'Call monitoring and quality assurance',
                                        'Agent' => 'Standard agent with call handling capabilities',
                                    ];
                                    $roleColors = [
                                        'Superadmin' => 'border-red-500 bg-red-50 dark:bg-red-900/20',
                                        'Admin' => 'border-primary-500 bg-primary-50 dark:bg-primary-900/20',
                                        'Manager' => 'border-accent-500 bg-accent-50 dark:bg-accent-900/20',
                                        'Quality Analyst' => 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20',
                                        'Agent' => 'border-gray-400 bg-gray-50 dark:bg-gray-700/50',
                                    ];
                                @endphp
                                <label class="flex items-start p-4 rounded-lg border-2 cursor-pointer transition-all hover:shadow-md
                                    {{ old('role') === $role->name ? $roleColors[$role->name] ?? '' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}">
                                    <input type="radio" name="role" value="{{ $role->name }}" 
                                           {{ old('role') === $role->name ? 'checked' : '' }}
                                           class="mt-1 text-primary-600 focus:ring-primary-500" required>
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $role->name }}</span>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ $roleDescriptions[$role->name] ?? 'System role' }}
                                        </p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label for="extension_id" class="form-label">Assign Extension</label>
                        <select name="extension_id" id="extension_id" class="form-select">
                            <option value="">No Extension</option>
                            @foreach($extensions as $extension)
                                <option value="{{ $extension->id }}" {{ old('extension_id') == $extension->id ? 'selected' : '' }}>
                                    {{ $extension->extension }} - {{ $extension->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Only unassigned extensions are shown</p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('users.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Create User
                </button>
            </div>
        </form>
    </div>
</x-app-layout>

