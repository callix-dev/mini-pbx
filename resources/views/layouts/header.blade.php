<header class="header-critical sticky top-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between h-16 px-4 lg:px-6">
        <!-- Left Side -->
        <div class="flex items-center space-x-4">
            <!-- Mobile Menu Button -->
            <button @click="sidebarMobileOpen = true" class="lg:hidden p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <!-- Breadcrumb / Page Title -->
            <div class="hidden sm:block">
                <h1 class="text-lg font-semibold text-gray-900 dark:text-white">
                    @yield('page-title', 'Dashboard')
                </h1>
            </div>
        </div>

        <!-- Right Side -->
        <div class="flex items-center space-x-3">
            <!-- Call Control Widget (shows during active calls) -->
            @if(auth()->user()?->extension)
            <div x-data="headerCallControl()" x-init="init()" x-cloak x-show="isInCall" 
                 class="flex items-center space-x-2 px-3 py-1.5 rounded-lg"
                 :class="{
                     'bg-yellow-100 dark:bg-yellow-900/30 border border-yellow-300 dark:border-yellow-700': callState === 'ringing',
                     'bg-blue-100 dark:bg-blue-900/30 border border-blue-300 dark:border-blue-700': callState === 'calling',
                     'bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700': callState === 'connected'
                 }">
                <!-- Call Type Icon -->
                <div class="flex items-center">
                    <template x-if="callDirection === 'inbound'">
                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </template>
                    <template x-if="callDirection === 'outbound'">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </template>
                </div>
                
                <!-- Caller Info -->
                <div class="flex flex-col leading-tight">
                    <span class="text-xs font-medium text-gray-900 dark:text-white truncate max-w-[100px]" 
                          x-text="callerName || callerNumber || 'Unknown'"></span>
                    <span x-show="callerName && callerNumber" class="text-[10px] text-gray-500 dark:text-gray-400" x-text="callerNumber"></span>
                </div>
                
                <!-- Duration -->
                <div class="flex items-center px-2 py-0.5 rounded bg-white/50 dark:bg-black/20">
                    <span class="text-xs font-mono font-semibold" 
                          :class="{
                              'text-yellow-700 dark:text-yellow-300': callState === 'ringing',
                              'text-blue-700 dark:text-blue-300': callState === 'calling',
                              'text-green-700 dark:text-green-300': callState === 'connected'
                          }"
                          x-text="callDuration"></span>
                </div>
                
                <!-- Status Badges -->
                <div class="flex items-center space-x-1">
                    <span x-show="isMuted" class="px-1.5 py-0.5 text-[10px] font-medium bg-red-200 dark:bg-red-900/50 text-red-700 dark:text-red-300 rounded">
                        MUTE
                    </span>
                    <span x-show="isOnHold" class="px-1.5 py-0.5 text-[10px] font-medium bg-yellow-200 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300 rounded">
                        HOLD
                    </span>
                </div>
                
                <!-- Quick Actions -->
                <div class="flex items-center space-x-1 ml-1 border-l border-gray-300 dark:border-gray-600 pl-2">
                    <!-- Mute Toggle -->
                    <button @click="toggleMute()" 
                            class="flex items-center space-x-1 px-2 py-1 rounded text-xs font-medium transition-colors"
                            :class="isMuted 
                                ? 'bg-red-500 text-white hover:bg-red-600' 
                                : 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-500'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <template x-if="!isMuted">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                            </template>
                            <template x-if="isMuted">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                            </template>
                        </svg>
                        <span x-text="isMuted ? 'Unmute' : 'Mute'"></span>
                    </button>
                    
                    <!-- Hold Toggle -->
                    <button @click="toggleHold()" 
                            class="flex items-center space-x-1 px-2 py-1 rounded text-xs font-medium transition-colors"
                            :class="isOnHold 
                                ? 'bg-yellow-500 text-white hover:bg-yellow-600' 
                                : 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-500'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span x-text="isOnHold ? 'Resume' : 'Hold'"></span>
                    </button>
                    
                    <!-- Hangup -->
                    <button @click="hangup()" 
                            class="flex items-center space-x-1 px-2 py-1 rounded text-xs font-medium bg-red-500 hover:bg-red-600 text-white transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z"/>
                        </svg>
                        <span>Hangup</span>
                    </button>
                    
                    <!-- Open Webphone -->
                    <button @click="openWebphone()" 
                            class="flex items-center space-x-1 px-2 py-1 rounded text-xs font-medium bg-primary-500 hover:bg-primary-600 text-white transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        <span>Phone</span>
                    </button>
                </div>
            </div>
            @endif
            
            <!-- Search -->
            <div class="hidden md:block relative">
                <input type="text" 
                       placeholder="Search..." 
                       class="w-64 pl-10 pr-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 border-0 rounded-lg focus:ring-2 focus:ring-primary-500 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400">
                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <!-- Agent Status Indicator -->
            @auth
            @php $defaultStatus = auth()->user()->agent_status ?? 'offline'; @endphp
            <div x-data="agentStatus('{{ $defaultStatus }}')" x-init="init()" class="relative">
                <button @click="open = !open" class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                              :class="{
                                  'bg-green-400': status === 'available',
                                  'bg-yellow-400': status === 'on_break' || status === 'not_ready',
                                  'bg-red-400': status === 'on_call',
                                  'bg-orange-400': status === 'ringing',
                                  'bg-gray-400': status === 'offline'
                              }"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3"
                              :class="{
                                  'bg-green-500': status === 'available',
                                  'bg-yellow-500': status === 'on_break' || status === 'not_ready',
                                  'bg-red-500': status === 'on_call',
                                  'bg-orange-500': status === 'ringing',
                                  'bg-gray-500': status === 'offline'
                              }"></span>
                    </span>
                    <span class="hidden sm:inline text-sm font-medium text-gray-700 dark:text-gray-300 capitalize min-w-[4.5rem]" x-text="status.replace('_', ' ')">{{ str_replace('_', ' ', $defaultStatus) }}</span>
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <!-- Status Dropdown -->
                <div x-cloak="dropdown" x-show="open" @click.away="open = false"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50">
                    <button @click="setStatus('available')" class="w-full flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <span class="w-3 h-3 rounded-full bg-green-500 mr-3"></span>
                        Available
                    </button>
                    <button @click="setStatus('on_break')" class="w-full flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <span class="w-3 h-3 rounded-full bg-yellow-500 mr-3"></span>
                        On Break
                    </button>
                    <button @click="setStatus('not_ready')" class="w-full flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <span class="w-3 h-3 rounded-full bg-yellow-500 mr-3"></span>
                        Not Ready
                    </button>
                    <button @click="setStatus('offline')" class="w-full flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <span class="w-3 h-3 rounded-full bg-gray-500 mr-3"></span>
                        Offline
                    </button>
                </div>
            </div>
            @endauth

            <!-- Waiting Calls Widget -->
            @auth
            @if(auth()->user()->extension || auth()->user()->hasAnyRole(['admin', 'qa', 'manager', 'super-admin']))
            <div x-data="waitingCallsWidget()" x-init="init()" class="relative">
                <button @click="open = !open" 
                        class="relative p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700"
                        :class="{ 'text-orange-500 dark:text-orange-400': waitingCalls.length > 0 }">
                    <!-- Queue/Phone Icon -->
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <!-- Badge -->
                    <span x-show="waitingCalls.length > 0" x-cloak
                          class="absolute -top-1 -right-1 min-w-[18px] h-[18px] flex items-center justify-center text-[10px] font-bold text-white bg-orange-500 rounded-full px-1"
                          x-text="waitingCalls.length"></span>
                </button>

                <!-- Dropdown -->
                <div x-cloak x-show="open" @click.away="open = false"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50 overflow-hidden">
                    
                    <!-- Header -->
                    <div class="px-4 py-3 bg-gradient-to-r from-orange-500 to-amber-500 text-white">
                        <h3 class="font-semibold flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            Waiting Calls
                        </h3>
                        <p class="text-xs text-white/80" x-text="waitingCalls.length + ' call(s) waiting'"></p>
                    </div>
                    
                    <!-- No calls -->
                    <div x-show="waitingCalls.length === 0" class="p-4 text-center text-gray-500 dark:text-gray-400">
                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm">No calls waiting</p>
                    </div>
                    
                    <!-- Waiting calls list -->
                    <div x-show="waitingCalls.length > 0" class="max-h-64 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                        <template x-for="call in waitingCalls" :key="call.channel">
                            <div class="p-3 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="call.caller_name || call.caller_id"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400" x-show="call.caller_name" x-text="call.caller_id"></p>
                                        <div class="flex items-center mt-1 space-x-2 text-xs text-gray-500 dark:text-gray-400">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400" x-text="call.group_name"></span>
                                            <span class="flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <span x-text="call.wait_formatted"></span>
                                            </span>
                                        </div>
                                    </div>
                                    <button @click="pickupCall(call.channel)" 
                                            class="ml-2 px-3 py-1.5 text-xs font-medium bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors flex items-center"
                                            :disabled="pickingUp === call.channel">
                                        <svg x-show="pickingUp !== call.channel" class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        <svg x-show="pickingUp === call.channel" class="animate-spin w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Pickup
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                    
                    <!-- Footer link to dashboard -->
                    <div class="px-4 py-2 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700">
                        <a href="{{ route('dashboard') }}#waiting-calls" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">
                            View all on dashboard
                        </a>
                    </div>
                </div>
            </div>
            @endif
            @endauth

            <!-- Notifications -->
            <button class="relative p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>

            <!-- Dark Mode Toggle -->
            <button @click="darkMode = !darkMode" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                <svg x-show="!darkMode" class="w-6 h-6 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                <svg x-show="darkMode" class="w-6 h-6 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </button>

            <!-- User Dropdown -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white font-medium">
                        {{ auth()->user() ? strtoupper(substr(auth()->user()->name, 0, 1)) : 'U' }}
                    </div>
                    <div class="hidden md:block text-left">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ auth()->user()->name ?? 'User' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->roles->first()->name ?? 'User' }}</p>
                    </div>
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <!-- User Dropdown Menu -->
                <div x-cloak="dropdown" x-show="open" @click.away="open = false"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50">
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ auth()->user()->name ?? 'User' }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ auth()->user()->email ?? '' }}</p>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        My Profile
                    </a>
                    <a href="{{ route('callbacks.index') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        My Callbacks
                    </a>
                    <div class="border-t border-gray-200 dark:border-gray-700 mt-1 pt-1">
                        <form method="POST" action="{{ route('logout') }}" id="logout-form" 
                              onsubmit="if(window.phoneSync){window.phoneSync.broadcastLogout()}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                Sign Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

@auth
<script>
function headerCallControl() {
    return {
        callState: 'idle',
        callerNumber: '',
        callerName: '',
        callDuration: '00:00',
        callDirection: '',
        isMuted: false,
        isOnHold: false,
        channel: null,
        
        get isInCall() {
            return ['ringing', 'calling', 'connected'].includes(this.callState);
        },
        
        init() {
            // Listen for state updates from webphone via BroadcastChannel
            this.channel = new BroadcastChannel('mini-pbx-phone');
            this.channel.onmessage = (event) => {
                if (event.data.type === 'state_update' && event.data.state) {
                    this.updateState(event.data.state);
                }
            };
            
            // Also check localStorage for initial state
            const stored = localStorage.getItem('mini-pbx-phone-state');
            if (stored) {
                try {
                    const data = JSON.parse(stored);
                    if (data.state && Date.now() - data.timestamp < 30000) {
                        this.updateState(data.state);
                    }
                } catch (e) {}
            }
            
            // Listen via webphone_sync channel too
            const syncChannel = new BroadcastChannel('webphone_sync');
            syncChannel.onmessage = (event) => {
                if (event.data.type === 'statechange' && event.data.state) {
                    // Handle partial state updates
                    if (event.data.state.callState !== undefined) this.callState = event.data.state.callState;
                    if (event.data.state.callerNumber !== undefined) this.callerNumber = event.data.state.callerNumber;
                    if (event.data.state.callerName !== undefined) this.callerName = event.data.state.callerName;
                    if (event.data.state.callDuration !== undefined) this.callDuration = event.data.state.callDuration;
                    if (event.data.state.callDirection !== undefined) this.callDirection = event.data.state.callDirection;
                    if (event.data.state.isMuted !== undefined) this.isMuted = event.data.state.isMuted;
                    if (event.data.state.isOnHold !== undefined) this.isOnHold = event.data.state.isOnHold;
                }
            };
        },
        
        updateState(state) {
            this.callState = state.callState || 'idle';
            this.callerNumber = state.callerNumber || '';
            this.callerName = state.callerName || '';
            this.callDuration = state.callDuration || '00:00';
            this.callDirection = state.callDirection || '';
            this.isMuted = state.isMuted || false;
            this.isOnHold = state.isOnHold || false;
        },
        
        sendCommand(type) {
            // Send command to webphone popup
            const channel = new BroadcastChannel('webphone_sync');
            channel.postMessage({ type: type });
            channel.close();
            
            // Also via localStorage
            localStorage.setItem('mini-pbx-phone-command', JSON.stringify({
                type: type,
                timestamp: Date.now()
            }));
        },
        
        toggleMute() {
            this.sendCommand('toggle_mute');
        },
        
        toggleHold() {
            this.sendCommand('toggle_hold');
        },
        
        hangup() {
            this.sendCommand('hangup');
        },
        
        openWebphone() {
            const width = 360;
            const height = 640;
            const left = window.screen.width - width - 50;
            const top = (window.screen.height - height) / 2;
            
            window.open(
                '',
                'mini-pbx-softphone'
            )?.focus();
        }
    }
}

function agentStatus(initialStatus) {
    return {
        open: false,
        status: initialStatus,
        pollInterval: null,

        init() {
            // Poll status every 2 seconds
            this.pollStatus();
            this.pollInterval = setInterval(() => this.pollStatus(), 2000);
            
            // Listen for incoming calls
            this.listenForCalls();
        },

        async pollStatus() {
            try {
                const response = await fetch('/api/user/status');
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.status !== this.status) {
                        this.status = data.status;
                    }
                }
            } catch (e) {
                // Silent fail
            }
        },

        async setStatus(newStatus) {
            this.open = false;
            const prevStatus = this.status;
            this.status = newStatus;

            try {
                const response = await fetch('/api/webphone/status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ status: newStatus })
                });

                if (!response.ok) {
                    this.status = prevStatus; // Revert on error
                }
            } catch (e) {
                this.status = prevStatus;
            }
        },

        listenForCalls() {
            // Listen for incoming call events from Echo/Reverb
            if (window.Echo) {
                window.Echo.private('user.{{ auth()->id() }}')
                    .listen('IncomingCall', (e) => {
                        this.showIncomingCallPopup(e.call);
                    });
            }

            // Also listen for BroadcastChannel messages from webphone
            const channel = new BroadcastChannel('webphone_sync');
            channel.onmessage = (event) => {
                if (event.data.type === 'incoming_call') {
                    this.showIncomingCallPopup(event.data.call);
                }
                if (event.data.type === 'statechange' && event.data.state) {
                    if (event.data.state.isInCall) {
                        this.status = 'on_call';
                    } else if (event.data.state.isRegistered) {
                        // Only update if we're currently showing on_call
                        if (this.status === 'on_call') {
                            this.status = 'available';
                        }
                    }
                }
            };
        },

        showIncomingCallPopup(call) {
            window.dispatchEvent(new CustomEvent('incoming-call', { detail: call }));
        }
    }
}

function waitingCallsWidget() {
    return {
        open: false,
        waitingCalls: [],
        pickingUp: null,
        pollInterval: null,
        
        init() {
            // Initial fetch
            this.fetchWaitingCalls();
            
            // Poll every 2 seconds
            this.pollInterval = setInterval(() => this.fetchWaitingCalls(), 2000);
        },
        
        async fetchWaitingCalls() {
            try {
                const response = await fetch('/api/waiting-calls');
                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        this.waitingCalls = data.waiting_calls || [];
                    }
                }
            } catch (e) {
                console.error('Failed to fetch waiting calls:', e);
            }
        },
        
        async pickupCall(channel) {
            if (this.pickingUp) return;
            
            this.pickingUp = channel;
            
            try {
                const response = await fetch(`/api/waiting-calls/${encodeURIComponent(channel)}/pickup`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Remove from list immediately
                    this.waitingCalls = this.waitingCalls.filter(c => c.channel !== channel);
                    this.open = false;
                } else {
                    alert(data.message || 'Failed to pickup call');
                }
            } catch (e) {
                console.error('Pickup failed:', e);
                alert('Failed to pickup call');
            } finally {
                this.pickingUp = null;
            }
        },
        
        destroy() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
            }
        }
    }
}
</script>
@endauth

