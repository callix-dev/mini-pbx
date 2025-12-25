<x-app-layout>
    @section('title', 'Edit User: ' . $user->name)
    @section('page-title', 'Edit User')

    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('users.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Edit User: {{ $user->name }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Update user information and permissions</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

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
                            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" 
                                   class="form-input @error('name') border-red-500 @enderror" 
                                   placeholder="John Doe" required>
                            @error('name')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="form-label">Email Address <span class="text-red-500">*</span></label>
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" 
                                   class="form-input @error('email') border-red-500 @enderror" 
                                   placeholder="john@example.com" required>
                            @error('email')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" name="password" id="password" 
                                   class="form-input @error('password') border-red-500 @enderror" 
                                   placeholder="Leave blank to keep current" minlength="8">
                            <p class="mt-1 text-xs text-gray-500">Leave empty to keep current password</p>
                            @error('password')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="form-label">Confirm New Password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" 
                                   class="form-input" 
                                   placeholder="Confirm new password" minlength="8">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" 
                                   class="form-input" 
                                   placeholder="+1 234 567 8900">
                        </div>

                        <div>
                            <label for="timezone" class="form-label">Timezone</label>
                            <select name="timezone" id="timezone" class="form-select">
                                @php
                                    $selectedTimezone = old('timezone', $user->timezone ?? 'UTC');
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
                                    $currentRole = $user->roles->first()?->name;
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
                                    $isSelected = old('role', $currentRole) === $role->name;
                                @endphp
                                <label class="flex items-start p-4 rounded-lg border-2 cursor-pointer transition-all hover:shadow-md
                                    {{ $isSelected ? $roleColors[$role->name] ?? '' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}">
                                    <input type="radio" name="role" value="{{ $role->name }}" 
                                           {{ $isSelected ? 'checked' : '' }}
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
                                <option value="{{ $extension->id }}" {{ old('extension_id', $user->extension_id) == $extension->id ? 'selected' : '' }}>
                                    {{ $extension->extension }} - {{ $extension->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Only unassigned extensions and current user's extension are shown</p>
                    </div>
                </div>
            </div>

            <!-- Account Status -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Account Information</h3>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Created</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $user->created_at->format('M d, Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Last Login</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                            <dd>
                                @if($user->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('users.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Update User
                </button>
            </div>
        </form>

        <!-- Danger Zone -->
        @if($user->id !== auth()->id())
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-red-200 dark:border-red-700 mt-6">
                <div class="px-6 py-4 border-b border-red-200 dark:border-red-700">
                    <h3 class="text-lg font-medium text-red-600 dark:text-red-400">Danger Zone</h3>
                </div>
                <div class="p-6 space-y-4">
                    <!-- Reset Password -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-base font-medium text-gray-900 dark:text-white">Reset Password</h4>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Generate a new random password for this user.
                            </p>
                        </div>
                        <form action="{{ route('users.reset-password', $user) }}" method="POST"
                              onsubmit="return confirm('Are you sure you want to reset this user\'s password?')">
                            @csrf
                            <button type="submit" class="btn-warning">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                                Reset Password
                            </button>
                        </form>
                    </div>

                    <hr class="border-gray-200 dark:border-gray-700">

                    <!-- Delete User -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-base font-medium text-gray-900 dark:text-white">Delete User</h4>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Permanently delete this user account. This action cannot be undone.
                            </p>
                        </div>
                        <form action="{{ route('users.destroy', $user) }}" method="POST"
                              onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Delete User
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>

