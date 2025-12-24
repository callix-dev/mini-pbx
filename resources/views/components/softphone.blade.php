<!-- WebRTC Softphone Panel -->
@if(auth()->user()->extension)
<div x-data="softphone()" 
     x-init="init()"
     data-has-extension="true"
     @webphone:statechange.window="handleStateChange($event.detail)"
     class="fixed bottom-4 right-4 z-50">
    
    <!-- Softphone Toggle Button -->
    <button @click="isOpen = !isOpen; isMinimized = false" 
            class="relative bg-primary-600 hover:bg-primary-700 text-white rounded-full p-4 shadow-lg transition-all duration-200"
            :class="{ 
                'animate-pulse ring-4 ring-green-400': callState === 'ringing',
                'ring-2 ring-green-500': isRegistered && callState === 'idle'
            }">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
        </svg>
        <!-- Registration status indicator -->
        <span class="absolute -top-1 -right-1 w-3 h-3 rounded-full border-2 border-white"
              :class="isRegistered ? 'bg-green-500' : 'bg-red-500'"></span>
    </button>

    <!-- Softphone Panel -->
    <div x-show="isOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute bottom-16 right-0 w-80 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden"
         style="display: none;">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-primary-600 to-accent-600 text-white px-4 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                <span class="font-medium">Softphone</span>
            </div>
            <div class="flex items-center space-x-2">
                <button @click="isMinimized = !isMinimized" class="p-1 hover:bg-white/20 rounded">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                    </svg>
                </button>
                <button @click="isOpen = false" class="p-1 hover:bg-white/20 rounded">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Status Bar -->
        <div class="px-4 py-2 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between text-sm">
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full" :class="isRegistered ? 'bg-green-500' : 'bg-red-500'"></span>
                <span class="text-gray-600 dark:text-gray-400" x-text="isRegistered ? 'Registered' : 'Disconnected'"></span>
            </div>
            <span class="text-gray-500 dark:text-gray-400 font-mono">Ext: {{ auth()->user()->extension->extension }}</span>
        </div>

        <!-- Main Content -->
        <div x-show="!isMinimized" class="p-4">
            <!-- Error State -->
            <div x-show="errorMessage" class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <p class="text-sm text-red-600 dark:text-red-400" x-text="errorMessage"></p>
                <button @click="reconnect()" class="mt-2 text-sm text-red-700 dark:text-red-300 underline">Try Reconnect</button>
            </div>

            <!-- Idle State - Dial Pad -->
            <div x-show="callState === 'idle' || callState === 'registered'">
                <!-- Display -->
                <div class="relative mb-4">
                    <input type="text" 
                           x-model="dialNumber" 
                           x-ref="dialInput"
                           @keydown.enter="makeCall()"
                           placeholder="Enter number..."
                           class="w-full text-center text-xl font-mono bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg py-3 pr-10 focus:ring-primary-500 focus:border-primary-500">
                    <button x-show="dialNumber" 
                            @click="dialNumber = dialNumber.slice(0, -1)" 
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Dial Pad -->
                <div class="grid grid-cols-3 gap-2 mb-4">
                    <template x-for="key in ['1', '2', '3', '4', '5', '6', '7', '8', '9', '*', '0', '#']" :key="key">
                        <button @click="addDigit(key)" 
                                class="py-3 text-lg font-medium bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors active:scale-95"
                                x-text="key">
                        </button>
                    </template>
                </div>

                <!-- Call Button -->
                <button @click="makeCall()" 
                        :disabled="!dialNumber || !isRegistered"
                        :class="{ 'opacity-50 cursor-not-allowed': !dialNumber || !isRegistered }"
                        class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-medium flex items-center justify-center space-x-2 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <span>Call</span>
                </button>
            </div>

            <!-- Calling State -->
            <div x-show="callState === 'calling'" class="text-center">
                <div class="mb-4">
                    <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Calling...</p>
                    <p class="text-xl font-mono text-gray-900 dark:text-white" x-text="callerNumber"></p>
                </div>

                <!-- Cancel Button -->
                <button @click="hangUp()" 
                        class="w-full bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg font-medium flex items-center justify-center space-x-2 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z"/>
                    </svg>
                    <span>Cancel</span>
                </button>
            </div>

            <!-- In Call State -->
            <div x-show="callState === 'connected'" class="text-center">
                <div class="mb-4">
                    <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <p class="text-lg font-medium text-gray-900 dark:text-white" x-text="callerName || callerNumber"></p>
                    <p class="text-2xl font-mono text-green-600 dark:text-green-400" x-text="callDuration"></p>
                </div>

                <!-- In-Call Controls -->
                <div class="grid grid-cols-4 gap-2 mb-4">
                    <button @click="toggleMute()" 
                            :class="{ 'bg-red-100 dark:bg-red-900/30 text-red-600': isMuted }"
                            class="p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex flex-col items-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="!isMuted" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                            <path x-show="isMuted" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                        </svg>
                        <span class="text-xs mt-1">Mute</span>
                    </button>
                    <button @click="toggleHold()"
                            :class="{ 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600': isOnHold }"
                            class="p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex flex-col items-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-xs mt-1">Hold</span>
                    </button>
                    <button @click="showTransfer = !showTransfer" 
                            class="p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex flex-col items-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        <span class="text-xs mt-1">Transfer</span>
                    </button>
                    <button @click="showKeypad = !showKeypad" 
                            class="p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex flex-col items-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        <span class="text-xs mt-1">Keypad</span>
                    </button>
                </div>

                <!-- Transfer Input (shown when transfer button clicked) -->
                <div x-show="showTransfer" x-transition class="mb-4">
                    <div class="flex space-x-2">
                        <input type="text" 
                               x-model="transferNumber" 
                               placeholder="Transfer to..."
                               class="flex-1 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg py-2 px-3 focus:ring-primary-500 focus:border-primary-500">
                        <button @click="transfer()" 
                                :disabled="!transferNumber"
                                class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm disabled:opacity-50">
                            Transfer
                        </button>
                    </div>
                </div>

                <!-- In-call Keypad (shown when keypad button clicked) -->
                <div x-show="showKeypad" x-transition class="mb-4">
                    <div class="grid grid-cols-3 gap-1">
                        <template x-for="key in ['1', '2', '3', '4', '5', '6', '7', '8', '9', '*', '0', '#']" :key="key">
                            <button @click="sendDTMF(key)" 
                                    class="py-2 text-sm font-medium bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors"
                                    x-text="key">
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Hang Up Button -->
                <button @click="hangUp()" 
                        class="w-full bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg font-medium flex items-center justify-center space-x-2 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z"/>
                    </svg>
                    <span>End Call</span>
                </button>
            </div>

            <!-- Ringing State (Incoming Call) -->
            <div x-show="callState === 'ringing'" class="text-center">
                <div class="mb-4">
                    <div class="w-16 h-16 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center mx-auto mb-3 animate-pulse">
                        <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <p class="text-lg font-medium text-gray-900 dark:text-white">Incoming Call</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400" x-text="callerName" x-show="callerName"></p>
                    <p class="text-xl font-mono text-gray-900 dark:text-white" x-text="callerNumber"></p>
                </div>

                <div class="flex space-x-3">
                    <button @click="hangUp()" 
                            class="flex-1 bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg font-medium transition-colors">
                        <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Decline
                    </button>
                    <button @click="answerCall()" 
                            class="flex-1 bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-medium transition-colors">
                        <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Answer
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function softphone() {
    return {
        isOpen: false,
        isMinimized: false,
        isRegistered: false,
        callState: 'idle',
        callDuration: '00:00',
        callerNumber: '',
        callerName: '',
        dialNumber: '',
        transferNumber: '',
        isMuted: false,
        isOnHold: false,
        showTransfer: false,
        showKeypad: false,
        errorMessage: '',
        
        init() {
            // WebPhone will auto-initialize via webphone.js
            console.log('Softphone Alpine component initialized');
        },
        
        handleStateChange(detail) {
            console.log('Softphone received state change:', detail);
            
            if (detail.state) {
                this.callState = detail.state;
                
                if (detail.state === 'registered') {
                    this.isRegistered = true;
                    this.errorMessage = '';
                } else if (detail.state === 'unregistered' || detail.state === 'disconnected') {
                    this.isRegistered = false;
                } else if (detail.state === 'error') {
                    this.errorMessage = detail.message || 'Connection error';
                } else if (detail.state === 'idle') {
                    this.resetCallState();
                }
            }
            
            if (detail.number) {
                this.callerNumber = detail.number;
            }
            if (detail.name) {
                this.callerName = detail.name;
            }
            if (detail.callDuration) {
                this.callDuration = detail.callDuration;
            }
            if (typeof detail.isOnHold !== 'undefined') {
                this.isOnHold = detail.isOnHold;
            }
        },
        
        resetCallState() {
            this.callerNumber = '';
            this.callerName = '';
            this.callDuration = '00:00';
            this.isMuted = false;
            this.isOnHold = false;
            this.showTransfer = false;
            this.showKeypad = false;
            this.transferNumber = '';
        },
        
        addDigit(digit) {
            this.dialNumber += digit;
            // Play DTMF tone feedback (optional)
        },
        
        makeCall() {
            if (this.dialNumber && window.webPhone) {
                this.callerNumber = this.dialNumber;
                window.webPhone.call(this.dialNumber);
                this.dialNumber = '';
            }
        },
        
        answerCall() {
            if (window.webPhone) {
                window.webPhone.answer();
            }
        },
        
        hangUp() {
            if (window.webPhone) {
                window.webPhone.hangup();
            }
        },
        
        toggleMute() {
            if (window.webPhone) {
                this.isMuted = !this.isMuted;
                window.webPhone.mute(this.isMuted);
            }
        },
        
        toggleHold() {
            if (window.webPhone) {
                this.isOnHold = !this.isOnHold;
                window.webPhone.hold(this.isOnHold);
            }
        },
        
        transfer() {
            if (this.transferNumber && window.webPhone) {
                window.webPhone.transfer(this.transferNumber);
                this.showTransfer = false;
                this.transferNumber = '';
            }
        },
        
        sendDTMF(digit) {
            if (window.webPhone) {
                window.webPhone.sendDTMF(digit);
            }
        },
        
        reconnect() {
            this.errorMessage = '';
            if (window.webPhone) {
                window.webPhone.connect();
            }
        }
    }
}
</script>
@endpush
@else
<!-- No extension assigned message -->
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
