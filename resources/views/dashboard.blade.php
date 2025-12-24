<x-app-layout>
    @section('title', 'Dashboard')
    @section('page-title', 'Dashboard')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Welcome back, {{ auth()->user()->name }}!
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Here's what's happening with your PBX system today.
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ now()->format('l, F j, Y') }}
                </span>
            </div>
        </div>
    </x-slot>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Active Calls -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Calls</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">12</p>
                    <p class="text-sm text-green-600 dark:text-green-400 mt-1">
                        <span class="inline-flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            +3 from last hour
                        </span>
                    </p>
                </div>
                <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Agents Online -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Agents Online</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">24</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        <span class="text-green-600 dark:text-green-400">18 available</span> · 
                        <span class="text-yellow-600 dark:text-yellow-400">6 on call</span>
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Queue Waiting -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Queue Waiting</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">8</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Avg wait: <span class="text-accent-600 dark:text-accent-400">2m 34s</span>
                    </p>
                </div>
                <div class="w-12 h-12 bg-accent-100 dark:bg-accent-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-accent-600 dark:text-accent-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Today's Calls -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Today's Calls</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">1,284</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        <span class="text-green-600 dark:text-green-400">92%</span> answered
                    </p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Live Calls Panel -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Live Calls</h3>
                <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-sm font-medium rounded-full">
                    12 Active
                </span>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <!-- Call Item -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">+1 (555) 123-4567</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Agent: John Smith · Sales Queue</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm font-mono text-gray-600 dark:text-gray-300">05:23</span>
                            <div class="flex space-x-2">
                                <button class="p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg" title="Listen">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                                    </svg>
                                </button>
                                <button class="p-2 text-gray-400 hover:text-yellow-600 dark:hover:text-yellow-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg" title="Whisper">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                </button>
                                <button class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg" title="Barge">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- More Call Items... -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">+1 (555) 987-6543</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Agent: Sarah Wilson · Support Queue</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm font-mono text-gray-600 dark:text-gray-300">12:45</span>
                            <div class="flex space-x-2">
                                <button class="p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg" title="Listen">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                                    </svg>
                                </button>
                                <button class="p-2 text-gray-400 hover:text-yellow-600 dark:hover:text-yellow-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg" title="Whisper">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                </button>
                                <button class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg" title="Barge">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Agent Status Panel -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Agent Status</h3>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    <!-- Agent Item -->
                    <div class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="relative">
                                <div class="w-10 h-10 bg-primary-600 rounded-full flex items-center justify-center text-white font-medium">
                                    JS
                                </div>
                                <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white dark:border-gray-800 rounded-full"></span>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">John Smith</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Sales Queue</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full">
                            Available
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="relative">
                                <div class="w-10 h-10 bg-accent-600 rounded-full flex items-center justify-center text-white font-medium">
                                    SW
                                </div>
                                <span class="absolute bottom-0 right-0 w-3 h-3 bg-red-500 border-2 border-white dark:border-gray-800 rounded-full"></span>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Sarah Wilson</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Support Queue</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full">
                            On Call
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="relative">
                                <div class="w-10 h-10 bg-purple-600 rounded-full flex items-center justify-center text-white font-medium">
                                    MB
                                </div>
                                <span class="absolute bottom-0 right-0 w-3 h-3 bg-yellow-500 border-2 border-white dark:border-gray-800 rounded-full"></span>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Mike Brown</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Sales Queue</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-full">
                            Lunch
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="relative">
                                <div class="w-10 h-10 bg-pink-600 rounded-full flex items-center justify-center text-white font-medium">
                                    EJ
                                </div>
                                <span class="absolute bottom-0 right-0 w-3 h-3 bg-orange-500 border-2 border-white dark:border-gray-800 rounded-full"></span>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Emily Johnson</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Support Queue</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 text-xs font-medium bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 rounded-full">
                            Wrap-up
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Queue Statistics -->
    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Queue Status -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Queue Status</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 bg-primary-500 rounded-full"></div>
                            <span class="font-medium text-gray-900 dark:text-white">Sales Queue</span>
                        </div>
                        <div class="flex items-center space-x-6 text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Waiting: <strong class="text-gray-900 dark:text-white">4</strong></span>
                            <span class="text-gray-500 dark:text-gray-400">Agents: <strong class="text-gray-900 dark:text-white">8</strong></span>
                            <span class="text-gray-500 dark:text-gray-400">Avg: <strong class="text-gray-900 dark:text-white">1:45</strong></span>
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-primary-600 h-2 rounded-full" style="width: 65%"></div>
                    </div>

                    <div class="flex items-center justify-between pt-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 bg-accent-500 rounded-full"></div>
                            <span class="font-medium text-gray-900 dark:text-white">Support Queue</span>
                        </div>
                        <div class="flex items-center space-x-6 text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Waiting: <strong class="text-gray-900 dark:text-white">2</strong></span>
                            <span class="text-gray-500 dark:text-gray-400">Agents: <strong class="text-gray-900 dark:text-white">12</strong></span>
                            <span class="text-gray-500 dark:text-gray-400">Avg: <strong class="text-gray-900 dark:text-white">2:30</strong></span>
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-accent-600 h-2 rounded-full" style="width: 40%"></div>
                    </div>

                    <div class="flex items-center justify-between pt-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                            <span class="font-medium text-gray-900 dark:text-white">Billing Queue</span>
                        </div>
                        <div class="flex items-center space-x-6 text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Waiting: <strong class="text-gray-900 dark:text-white">0</strong></span>
                            <span class="text-gray-500 dark:text-gray-400">Agents: <strong class="text-gray-900 dark:text-white">4</strong></span>
                            <span class="text-gray-500 dark:text-gray-400">Avg: <strong class="text-gray-900 dark:text-white">0:45</strong></span>
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-purple-600 h-2 rounded-full" style="width: 15%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Parked Calls -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Parked Calls</h3>
                <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 text-sm font-medium rounded-full">
                    3 Parked
                </span>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Slot 701</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">+1 (555) 111-2222 · 2:15</p>
                        </div>
                        <button class="px-3 py-1 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                            Pickup
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Slot 702</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">+1 (555) 333-4444 · 0:45</p>
                        </div>
                        <button class="px-3 py-1 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                            Pickup
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Slot 703</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">+1 (555) 555-6666 · 4:30</p>
                        </div>
                        <button class="px-3 py-1 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                            Pickup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
