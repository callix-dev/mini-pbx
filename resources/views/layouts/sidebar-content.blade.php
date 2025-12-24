<div class="flex flex-col h-full">
    <!-- Logo -->
    <div class="flex items-center justify-between h-16 px-4 border-b border-sidebar-border">
        <a href="{{ route('dashboard') }}" class="flex items-center space-x-3">
            <div class="flex-shrink-0 w-10 h-10 bg-primary-600 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </div>
            <span x-show="sidebarOpen || sidebarMobileOpen" class="text-white font-bold text-lg">Mini PBX</span>
        </a>
        <!-- Close button for mobile -->
        <button @click="sidebarMobileOpen = false" class="lg:hidden text-gray-400 hover:text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <!-- Dashboard -->
        <x-sidebar-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')" icon="home">
            Dashboard
        </x-sidebar-link>

        <!-- Telephony Section -->
        <div class="pt-4">
            <p x-show="sidebarOpen || sidebarMobileOpen" class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                Telephony
            </p>
            <div x-show="!sidebarOpen && !sidebarMobileOpen" class="border-t border-sidebar-border my-2"></div>
        </div>

        <x-sidebar-link href="{{ route('extensions.index') }}" :active="request()->routeIs('extensions.*')" icon="phone">
            Extensions
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('extension-groups.index') }}" :active="request()->routeIs('extension-groups.*')" icon="user-group">
            Extension Groups
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('dids.index') }}" :active="request()->routeIs('dids.*')" icon="phone-incoming">
            DIDs
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('queues.index') }}" :active="request()->routeIs('queues.*')" icon="queue">
            Call Queues
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('ring-trees.index') }}" :active="request()->routeIs('ring-trees.*')" icon="tree">
            Ring Trees
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('ivrs.index') }}" :active="request()->routeIs('ivrs.*')" icon="ivr">
            IVR Builder
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('voicemails.index') }}" :active="request()->routeIs('voicemails.*')" icon="voicemail">
            Voicemail
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('block-filters.index') }}" :active="request()->routeIs('block-filters.*')" icon="ban">
            Block Filters
        </x-sidebar-link>

        <!-- Call Logs Section -->
        <div class="pt-4">
            <p x-show="sidebarOpen || sidebarMobileOpen" class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                Call Logs
            </p>
            <div x-show="!sidebarOpen && !sidebarMobileOpen" class="border-t border-sidebar-border my-2"></div>
        </div>

        <x-sidebar-link href="{{ route('call-logs.index') }}" :active="request()->routeIs('call-logs.index')" icon="call-log">
            All Calls
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('call-logs.analytics') }}" :active="request()->routeIs('call-logs.analytics')" icon="chart">
            Analytics
        </x-sidebar-link>

        <!-- Settings Section -->
        <div class="pt-4">
            <p x-show="sidebarOpen || sidebarMobileOpen" class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                Settings
            </p>
            <div x-show="!sidebarOpen && !sidebarMobileOpen" class="border-t border-sidebar-border my-2"></div>
        </div>

        <x-sidebar-link href="{{ route('carriers.index') }}" :active="request()->routeIs('carriers.*')" icon="server">
            Carriers
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('break-codes.index') }}" :active="request()->routeIs('break-codes.*')" icon="coffee">
            Break Codes
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('hold-music.index') }}" :active="request()->routeIs('hold-music.*')" icon="music">
            Hold Music
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('soundboards.index') }}" :active="request()->routeIs('soundboards.*')" icon="speaker">
            Soundboards
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('dispositions.index') }}" :active="request()->routeIs('dispositions.*')" icon="tag">
            Dispositions
        </x-sidebar-link>

        <!-- Platform Settings Section -->
        @can('manage-platform')
        <div class="pt-4">
            <p x-show="sidebarOpen || sidebarMobileOpen" class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                Platform
            </p>
            <div x-show="!sidebarOpen && !sidebarMobileOpen" class="border-t border-sidebar-border my-2"></div>
        </div>

        <x-sidebar-link href="{{ route('users.index') }}" :active="request()->routeIs('users.*')" icon="users">
            User Management
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('system-settings.index') }}" :active="request()->routeIs('system-settings.*')" icon="cog">
            System Settings
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('api-keys.index') }}" :active="request()->routeIs('api-keys.*')" icon="key">
            API Keys
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('audit-logs.index') }}" :active="request()->routeIs('audit-logs.*')" icon="clipboard">
            Audit Logs
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('backups.index') }}" :active="request()->routeIs('backups.*')" icon="database">
            Backups
        </x-sidebar-link>
        @endcan
    </nav>

    <!-- Sidebar Toggle (Desktop) -->
    <div class="hidden lg:flex items-center justify-center p-4 border-t border-sidebar-border">
        <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-lg text-gray-400 hover:text-white hover:bg-sidebar-hover transition-colors">
            <svg x-show="sidebarOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
            </svg>
            <svg x-show="!sidebarOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
            </svg>
        </button>
    </div>
</div>

