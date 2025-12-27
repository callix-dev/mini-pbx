<!-- WebRTC Softphone Status Widget -->
@if(auth()->user()->extension)
<div class="fixed bottom-4 right-4 z-50" x-data="softphoneStatus()" x-init="init()">
    
    <!-- Minimized View - Small circular button -->
    <template x-if="isMinimized && !isInCall">
        <button @click="isMinimized = false" 
                class="w-12 h-12 rounded-full bg-gradient-to-r from-primary-600 to-accent-600 shadow-lg hover:shadow-xl flex items-center justify-center text-white transition-all hover:scale-105"
                title="Open WebPhone widget">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
            </svg>
            <!-- Status dot -->
            <span class="absolute -top-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white"
                  :class="isRegistered ? 'bg-green-500' : 'bg-red-500'"></span>
        </button>
    </template>
    
    <!-- Main Widget Container (when expanded or in call) -->
    <div x-show="!isMinimized || isInCall" x-cloak
         class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden transition-all duration-300"
         :class="isInCall ? 'w-80' : 'w-64'"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-4">
        
        <!-- In Call View -->
        <template x-if="isInCall">
            <div>
                <!-- Call Status Header -->
                <div class="px-4 py-3 flex items-center justify-between"
                     :class="{
                        'bg-gradient-to-r from-yellow-500 to-orange-500': callState === 'ringing',
                        'bg-gradient-to-r from-blue-500 to-indigo-500': callState === 'calling',
                        'bg-gradient-to-r from-green-500 to-emerald-500': callState === 'connected'
                     }">
                    <div class="flex items-center space-x-3 text-white">
                        <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center"
                             :class="{ 'animate-pulse': callState === 'ringing' }">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-sm" x-text="getCallStateLabel()"></p>
                            <p class="text-xs text-white/80">
                                <span x-text="callDirection === 'inbound' ? '↓ Incoming' : '↑ Outgoing'"></span>
                            </p>
                        </div>
                    </div>
                    <div class="text-right text-white">
                        <p class="font-mono text-xl font-bold" x-text="callDuration"></p>
                    </div>
                </div>
                
                <!-- Caller Info -->
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-lg font-semibold text-gray-900 dark:text-white truncate" 
                               x-text="callerName || callerNumber || 'Unknown'"></p>
                            <p class="text-sm text-gray-500 dark:text-gray-400" 
                               x-show="callerName" x-text="callerNumber"></p>
                        </div>
                    </div>
                </div>
                
                <!-- Call Status Badges -->
                <div class="px-4 py-2 flex items-center justify-between bg-gray-50 dark:bg-gray-700/50">
                    <div class="flex items-center space-x-2">
                        <span x-show="isMuted" 
                              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                            </svg>
                            Muted
                        </span>
                        <span x-show="isOnHold" 
                              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            On Hold
                        </span>
                        <span x-show="!isMuted && !isOnHold && callState === 'connected'" 
                              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400">
                            <span class="w-2 h-2 bg-green-500 rounded-full mr-1 animate-pulse"></span>
                            Active
                        </span>
                    </div>
                    <button @click="openSoftphone()" 
                            class="p-2 text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
                            title="Open Phone Window">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </button>
                </div>
            </div>
        </template>
        
        <!-- Idle View (Not in call) -->
        <template x-if="!isInCall">
            <div>
                <!-- Status Header -->
                <div class="px-4 py-3 flex items-center justify-between bg-gradient-to-r from-primary-600 to-accent-600">
                    <div class="flex items-center space-x-3 text-white">
                        <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-sm">WebPhone</p>
                            <p class="text-xs text-white/80">Ext: {{ auth()->user()->extension->extension }}</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-1">
                        <span class="w-2 h-2 rounded-full" 
                              :class="isRegistered ? 'bg-green-400' : 'bg-red-400'"></span>
                        <span class="text-xs text-white/80" x-text="isRegistered ? 'Online' : 'Offline'"></span>
                    </div>
                </div>
                
                <!-- Action Button -->
                <div class="p-3">
                    <button @click="openSoftphone()" 
                            class="w-full flex items-center justify-center space-x-2 px-4 py-3 rounded-xl font-medium transition-all"
                            :class="isRegistered 
                                ? 'bg-primary-600 hover:bg-primary-700 text-white' 
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <span x-text="isWindowOpen ? 'View WebPhone' : 'Open WebPhone'"></span>
                        <svg x-show="!isWindowOpen" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </button>
                    
                    <!-- Quick Status Info -->
                    <div class="mt-2 flex items-center justify-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                        <span class="flex items-center space-x-1">
                            <span class="w-1.5 h-1.5 rounded-full" 
                                  :class="isRegistered ? 'bg-green-500' : 'bg-gray-400'"></span>
                            <span x-text="isRegistered ? 'Ready' : 'Not Connected'"></span>
                        </span>
                        <span x-show="isWindowOpen" class="flex items-center space-x-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Window Open</span>
                        </span>
                    </div>
                </div>
            </div>
        </template>
        
        <!-- Minimize Toggle Button (inside the widget) -->
        <button @click="isMinimized = true" 
                x-show="!isInCall"
                class="absolute -top-2 -left-2 w-6 h-6 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 rounded-full flex items-center justify-center text-gray-500 dark:text-gray-300 shadow-md transition-colors z-10"
                title="Minimize widget">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
    </div>
</div>

<script>
function softphoneStatus() {
    return {
        phoneWindow: null,
        isWindowOpen: false,
        isMinimized: localStorage.getItem('softphone-widget-minimized') === 'true',
        checkInterval: null,
        
        // Synced state from popup
        isRegistered: false,
        callState: 'idle',
        callerNumber: '',
        callerName: '',
        callDuration: '00:00',
        callDirection: '',
        isMuted: false,
        isOnHold: false,
        
        get isInCall() {
            return ['ringing', 'calling', 'connected'].includes(this.callState);
        },
        
        init() {
            // Watch for minimized state changes and persist
            this.$watch('isMinimized', (value) => {
                localStorage.setItem('softphone-widget-minimized', value ? 'true' : 'false');
            });
            
            // Subscribe to phone sync updates
            if (window.phoneSync) {
                window.phoneSync.subscribe((state) => {
                    this.isRegistered = state.isRegistered;
                    this.callState = state.callState;
                    this.callerNumber = state.callerNumber;
                    this.callerName = state.callerName;
                    this.callDuration = state.callDuration;
                    this.callDirection = state.callDirection;
                    this.isMuted = state.isMuted;
                    this.isOnHold = state.isOnHold;
                });
                
                // Request current state
                window.phoneSync.requestState();
            }
            
            // Check for existing phone window
            this.checkExistingWindow();
        },
        
        checkExistingWindow() {
            // Check if phone window is open via localStorage heartbeat
            const heartbeat = localStorage.getItem('mini-pbx-phone-heartbeat');
            if (heartbeat) {
                const lastBeat = parseInt(heartbeat, 10);
                // Window is open if heartbeat was within last 3 seconds
                this.isWindowOpen = (Date.now() - lastBeat) < 3000;
            }
            
            // Also check state for recent updates
            const stored = localStorage.getItem('mini-pbx-phone-state');
            if (stored) {
                try {
                    const data = JSON.parse(stored);
                    if (Date.now() - data.timestamp < 3000) {
                        this.isWindowOpen = true;
                    }
                } catch (e) {}
            }
            
            // Keep checking periodically
            setInterval(() => {
                const hb = localStorage.getItem('mini-pbx-phone-heartbeat');
                if (hb) {
                    this.isWindowOpen = (Date.now() - parseInt(hb, 10)) < 3000;
                } else {
                    this.isWindowOpen = false;
                }
            }, 1000);
        },
        
        getCallStateLabel() {
            switch (this.callState) {
                case 'ringing': return 'Incoming Call';
                case 'calling': return 'Calling...';
                case 'connected': return 'In Call';
                default: return 'Idle';
            }
        },
        
        openSoftphone() {
            // If we still have the reference and window is open, just focus
            if (this.phoneWindow && !this.phoneWindow.closed) {
                this.phoneWindow.focus();
                return;
            }
            
            // Check if window is already open via heartbeat (after page navigation)
            const heartbeat = localStorage.getItem('mini-pbx-phone-heartbeat');
            const isAlreadyOpen = heartbeat && (Date.now() - parseInt(heartbeat, 10)) < 3000;
            
            // Calculate position
            const width = 360;
            const height = 640;
            const left = window.screen.width - width - 50;
            const top = (window.screen.height - height) / 2;
            
            if (isAlreadyOpen) {
                // Window exists, use empty URL to just focus without reload
                this.phoneWindow = window.open(
                    '',  // Empty URL - focuses existing window without reload
                    'mini-pbx-softphone'
                );
            } else {
                // Open new popup
                this.phoneWindow = window.open(
                    '{{ route("softphone") }}',
                    'mini-pbx-softphone',
                    `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=no,status=no,menubar=no,toolbar=no,location=no`
                );
            }
            
            if (this.phoneWindow) {
                this.phoneWindow.focus();
                this.isWindowOpen = true;
                
                // Check periodically if window is still open
                if (this.checkInterval) clearInterval(this.checkInterval);
                this.checkInterval = setInterval(() => {
                    if (this.phoneWindow && this.phoneWindow.closed) {
                        this.isWindowOpen = false;
                        clearInterval(this.checkInterval);
                        this.phoneWindow = null;
                    }
                }, 1000);
            }
        }
    }
}
</script>
@else
<!-- No extension assigned -->
<div class="fixed bottom-4 right-4 z-50">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden w-64">
        <div class="px-4 py-3 bg-gray-100 dark:bg-gray-700 flex items-center space-x-3">
            <div class="w-10 h-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center text-gray-500 dark:text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-sm text-gray-700 dark:text-gray-200">WebPhone</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">No extension assigned</p>
            </div>
        </div>
        <div class="p-3">
            <p class="text-xs text-center text-gray-500 dark:text-gray-400">
                Contact your administrator to assign an extension.
            </p>
        </div>
    </div>
</div>
@endif
