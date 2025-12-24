<!-- WebRTC Softphone Status & Button -->
@if(auth()->user()->extension)
<div class="fixed bottom-4 right-4 z-50" x-data="softphoneStatus()" x-init="init()">
    
    <!-- Expanded Status Panel (shown during calls) -->
    <div x-show="isInCall" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="mb-3 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden w-72">
        
        <!-- Call Status Header -->
        <div class="px-4 py-3 flex items-center justify-between"
             :class="{
                'bg-yellow-500': callState === 'ringing',
                'bg-blue-500': callState === 'calling',
                'bg-green-500': callState === 'connected'
             }">
            <div class="flex items-center space-x-3 text-white">
                <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                    <svg class="w-5 h-5" :class="{ 'animate-pulse': callState === 'ringing' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium" x-text="getCallStateLabel()"></p>
                    <p class="text-xs text-white/80" x-text="callDirection === 'inbound' ? 'Incoming' : 'Outgoing'"></p>
                </div>
            </div>
            <button @click="openSoftphone()" class="p-2 hover:bg-white/20 rounded-lg transition-colors" title="Open Phone">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </button>
        </div>
        
        <!-- Caller Info -->
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <p class="text-lg font-semibold text-gray-900 dark:text-white" x-text="callerName || callerNumber || 'Unknown'"></p>
            <p class="text-sm text-gray-500 dark:text-gray-400" x-show="callerName" x-text="callerNumber"></p>
        </div>
        
        <!-- Call Duration & Status -->
        <div class="px-4 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <!-- Duration -->
                <div x-show="callState === 'connected'" class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="font-mono text-lg text-gray-900 dark:text-white" x-text="callDuration"></span>
                </div>
                
                <!-- Status Icons -->
                <div class="flex items-center space-x-2">
                    <span x-show="isMuted" class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                        </svg>
                        Muted
                    </span>
                    <span x-show="isOnHold" class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Hold
                    </span>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="flex items-center space-x-1">
                <button @click="openSoftphone()" 
                        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                        title="Open Phone">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Main Phone Button -->
    <button @click="openSoftphone()" 
            class="relative bg-primary-600 hover:bg-primary-700 text-white rounded-full p-4 shadow-lg transition-all duration-200 group"
            :class="{ 
                'ring-4 ring-yellow-400 animate-pulse': callState === 'ringing',
                'ring-2 ring-green-500': isRegistered && !isInCall,
                'ring-2 ring-blue-500': callState === 'calling',
                'ring-2 ring-green-400': callState === 'connected'
            }">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
        </svg>
        
        <!-- Status indicator -->
        <span class="absolute -top-1 -right-1 w-3 h-3 rounded-full border-2 border-white"
              :class="{
                  'bg-green-500': isRegistered && !isInCall,
                  'bg-yellow-500 animate-pulse': callState === 'ringing',
                  'bg-blue-500': callState === 'calling',
                  'bg-green-400': callState === 'connected',
                  'bg-gray-400': !isRegistered && !isInCall
              }"></span>
        
        <!-- Call duration badge -->
        <span x-show="callState === 'connected'" 
              class="absolute -top-2 -left-2 px-1.5 py-0.5 bg-green-500 text-white text-xs font-mono rounded-full"
              x-text="callDuration"></span>
        
        <!-- Tooltip (hidden during calls) -->
        <span x-show="!isInCall" 
              class="absolute bottom-full right-0 mb-2 px-3 py-1.5 bg-gray-900 text-white text-sm rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
            <span x-text="isWindowOpen ? 'Phone is open' : (isRegistered ? 'Ready' : 'Offline')"></span>
            <span class="text-gray-400 text-xs block">Ext: {{ auth()->user()->extension->extension }}</span>
        </span>
    </button>
</div>

<script>
function softphoneStatus() {
    return {
        phoneWindow: null,
        isWindowOpen: false,
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
            // Try to detect if phone window is already open
            const stored = localStorage.getItem('mini-pbx-phone-state');
            if (stored) {
                try {
                    const data = JSON.parse(stored);
                    if (Date.now() - data.timestamp < 5000) {
                        this.isWindowOpen = true;
                    }
                } catch (e) {}
            }
        },
        
        getCallStateLabel() {
            switch (this.callState) {
                case 'ringing': return 'Incoming Call';
                case 'calling': return 'Calling...';
                case 'connected': return 'On Call';
                default: return 'Idle';
            }
        },
        
        openSoftphone() {
            // If window exists and is open, focus it
            if (this.phoneWindow && !this.phoneWindow.closed) {
                this.phoneWindow.focus();
                return;
            }
            
            // Calculate position
            const width = 360;
            const height = 640;
            const left = window.screen.width - width - 50;
            const top = (window.screen.height - height) / 2;
            
            // Open popup
            this.phoneWindow = window.open(
                '{{ route("softphone") }}',
                'mini-pbx-softphone',
                `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=no,status=no,menubar=no,toolbar=no,location=no`
            );
            
            if (this.phoneWindow) {
                this.isWindowOpen = true;
                
                // Check periodically if window is still open
                this.checkInterval = setInterval(() => {
                    if (this.phoneWindow.closed) {
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
    <div x-data="{ showTooltip: false }" class="relative">
        <button @mouseenter="showTooltip = true" 
                @mouseleave="showTooltip = false"
                class="bg-gray-400 text-white rounded-full p-4 shadow-lg cursor-not-allowed opacity-60">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
            </svg>
        </button>
        <div x-show="showTooltip" 
             x-transition
             class="absolute bottom-full right-0 mb-2 px-3 py-2 bg-gray-900 text-white text-sm rounded-lg whitespace-nowrap">
            No extension assigned
            <div class="absolute bottom-0 right-4 transform translate-y-1/2 rotate-45 w-2 h-2 bg-gray-900"></div>
        </div>
    </div>
</div>
@endif
