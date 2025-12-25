<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      x-data="{ 
          darkMode: localStorage.getItem('darkMode') === 'true', 
          sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false', 
          sidebarMobileOpen: false 
      }" 
      x-init="
          $watch('darkMode', val => { localStorage.setItem('darkMode', val); document.documentElement.classList.toggle('dark', val); });
          $watch('sidebarOpen', val => { localStorage.setItem('sidebarOpen', val); document.documentElement.classList.toggle('sidebar-collapsed', !val); });
      ">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Mini PBX') }} - @yield('title', 'Dashboard')</title>

        <!-- Prevent FOUC: Apply dark mode and sidebar state immediately before anything renders -->
        <script>
            (function() {
                // Dark mode
                if (localStorage.getItem('darkMode') === 'true') {
                    document.documentElement.classList.add('dark');
                }
                // Sidebar state - collapsed when sidebarOpen is false
                if (localStorage.getItem('sidebarOpen') === 'false') {
                    document.documentElement.classList.add('sidebar-collapsed');
                } else {
                    document.documentElement.classList.add('sidebar-expanded');
                }
            })();
        </script>

        <!-- Critical CSS to prevent flash - MUST load before anything else -->
        <style>
            /* Hide dropdown/modal elements until Alpine initializes */
            [x-cloak="dropdown"] { display: none !important; }
            
            /* Background colors */
            html { background-color: #f9fafb; }
            html.dark { background-color: #111827; }
            
            /* CRITICAL: Hide mobile sidebar ALWAYS until opened */
            .mobile-sidebar-overlay,
            .mobile-sidebar {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
            }
            
            /* Sidebar background color to prevent white flash */
            .sidebar-critical {
                background-color: #1a1f2e;
            }
            
            /* Sidebar text - visible by default when expanded */
            .sidebar-text { display: inline; }
            .sidebar-divider { display: none; }
            
            /* Sidebar collapsed state - hide text, show dividers */
            .sidebar-collapsed .sidebar-text { display: none !important; }
            .sidebar-collapsed .sidebar-divider { display: block !important; }
            
            /* Sidebar critical styles - prevents width jump */
            @media (min-width: 1024px) {
                .sidebar-critical { 
                    width: 16rem;
                    position: fixed;
                    left: 0;
                    top: 0;
                    bottom: 0;
                    display: flex;
                    flex-direction: column;
                }
                .sidebar-collapsed .sidebar-critical { 
                    width: 5rem;
                }
                .main-content-critical {
                    margin-left: 16rem;
                }
                .sidebar-collapsed .main-content-critical {
                    margin-left: 5rem;
                }
            }
            
            /* Hide desktop sidebar on mobile */
            @media (max-width: 1023px) {
                .sidebar-critical {
                    display: none !important;
                }
                .main-content-critical {
                    margin-left: 0 !important;
                }
            }
            
            /* Header critical styles - prevent layout shift */
            .header-critical {
                position: sticky;
                top: 0;
                z-index: 30;
                min-height: 4rem;
            }
            html.dark .header-critical {
                background-color: #1f2937;
                border-color: #374151;
            }
            html:not(.dark) .header-critical {
                background-color: #ffffff;
                border-color: #e5e7eb;
            }
        </style>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @stack('styles')
    </head>
    <body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
        <div class="min-h-screen flex">
            <!-- Sidebar Overlay for Mobile - hidden by default -->
            <div x-cloak
                 x-show="sidebarMobileOpen" 
                 x-transition:enter="transition-opacity ease-linear duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="mobile-sidebar-overlay fixed inset-0 z-40 bg-gray-900/80 lg:hidden"
                 style="display: none !important;"
                 x-bind:style="sidebarMobileOpen ? 'display: block !important;' : 'display: none !important;'"
                 @click="sidebarMobileOpen = false">
            </div>

            <!-- Sidebar -->
            @include('layouts.sidebar')

            <!-- Main Content Area -->
            <div class="main-content-critical flex-1 flex flex-col min-w-0 transition-all duration-300" :class="{ 'lg:ml-64': sidebarOpen, 'lg:ml-20': !sidebarOpen }">
                <!-- Top Header -->
                @include('layouts.header')

                <!-- Page Content -->
                <main class="flex-1 p-4 lg:p-6 overflow-x-hidden">
                    <!-- Page Header -->
                    @isset($header)
                        <div class="mb-6">
                            {{ $header }}
                        </div>
                    @endisset

                    <!-- Flash Messages -->
                    @if(session('success'))
                        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                             class="mb-4 p-4 bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-400 rounded-lg flex items-center justify-between">
                            <span>{{ session('success') }}</span>
                            <button @click="show = false" class="text-green-700 dark:text-green-400 hover:text-green-900 dark:hover:text-green-200">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                             class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-400 rounded-lg flex items-center justify-between">
                            <span>{{ session('error') }}</span>
                            <button @click="show = false" class="text-red-700 dark:text-red-400 hover:text-red-900 dark:hover:text-red-200">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    @endif

                    {{-- Password Change Results Modal --}}
                    @if(session('changed_passwords') || session('created_extensions'))
                        @php $passwords = session('changed_passwords') ?? session('created_extensions'); @endphp
                        <div x-data="{ showModal: true }" x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
                                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75" @click="showModal = false"></div>
                                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Extension Credentials
                                        </h3>
                                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="px-6 py-4">
                                        <p class="text-sm text-amber-600 dark:text-amber-400 mb-4 flex items-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                            Save these credentials! They won't be shown again.
                                        </p>
                                        <div class="max-h-64 overflow-y-auto">
                                            <table class="w-full text-sm">
                                                <thead class="bg-gray-50 dark:bg-gray-700">
                                                    <tr>
                                                        <th class="px-3 py-2 text-left text-gray-600 dark:text-gray-300">Extension</th>
                                                        <th class="px-3 py-2 text-left text-gray-600 dark:text-gray-300">Password</th>
                                                        <th class="px-3 py-2 text-center text-gray-600 dark:text-gray-300">Copy</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                    @foreach($passwords as $item)
                                                        <tr>
                                                            <td class="px-3 py-2 font-mono text-gray-900 dark:text-white">{{ $item['extension'] }}</td>
                                                            <td class="px-3 py-2 font-mono text-gray-900 dark:text-white">{{ $item['password'] }}</td>
                                                            <td class="px-3 py-2 text-center">
                                                                <button type="button" onclick="navigator.clipboard.writeText('{{ $item['extension'] }}:{{ $item['password'] }}')" class="text-primary-600 hover:text-primary-800 dark:text-primary-400">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                                    </svg>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex items-center justify-between">
                                        <button type="button" onclick="copyAllCredentials()" class="btn-secondary text-sm">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                            </svg>
                                            Copy All
                                        </button>
                                        <button @click="showModal = false" class="btn-primary text-sm">Done</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <script>
                            function copyAllCredentials() {
                                const credentials = @json($passwords);
                                const text = credentials.map(c => c.extension + ':' + c.password).join('\n');
                                navigator.clipboard.writeText(text).then(() => {
                                    alert('All credentials copied to clipboard!');
                                });
                            }
                        </script>
                    @endif

                    {{ $slot }}
                </main>

                <!-- Footer -->
                <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-4 px-6">
                    <div class="text-center text-sm text-gray-500 dark:text-gray-400">
                        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                    </div>
                </footer>
            </div>
        </div>

        <!-- WebRTC Softphone Panel -->
        @auth
            @include('components.softphone')
        @endauth

        @stack('scripts')
    </body>
</html>
