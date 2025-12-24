<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Softphone - {{ config('app.name') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body {
            overflow: hidden;
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-900 text-gray-100" x-data="softphoneApp()" x-init="init()">
    
    <div class="h-screen flex flex-col" data-has-extension="true">
        <!-- Header -->
        <div class="bg-gradient-to-r from-primary-600 to-accent-600 text-white px-4 py-3 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="font-semibold text-sm">{{ $user->name }}</h1>
                    <p class="text-xs text-white/70">Ext: {{ $extension->extension }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full" :class="isRegistered ? 'bg-green-400' : 'bg-red-400'"></span>
                <span class="text-xs" x-text="isRegistered ? 'Online' : 'Offline'"></span>
            </div>
        </div>

        <!-- Status Bar -->
        <div class="bg-gray-800 px-4 py-2 border-b border-gray-700 flex-shrink-0">
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center space-x-2">
                    <template x-if="callState === 'idle' || callState === 'registered'">
                        <span class="text-gray-400">Ready to make calls</span>
                    </template>
                    <template x-if="callState === 'calling'">
                        <span class="text-blue-400">Calling...</span>
                    </template>
                    <template x-if="callState === 'ringing'">
                        <span class="text-yellow-400 animate-pulse">Incoming Call</span>
                    </template>
                    <template x-if="callState === 'connected'">
                        <span class="text-green-400">In Call - <span x-text="callDuration"></span></span>
                    </template>
                </div>
                <template x-if="errorMessage">
                    <span class="text-red-400 text-xs" x-text="errorMessage"></span>
                </template>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col p-4 overflow-hidden">
            
            <!-- Idle State - Dial Pad -->
            <div x-show="callState === 'idle' || callState === 'registered'" class="flex-1 flex flex-col">
                <!-- Display -->
                <div class="relative mb-4">
                    <input type="text" 
                           x-model="dialNumber" 
                           x-ref="dialInput"
                           @keydown.enter="makeCall()"
                           placeholder="Enter number..."
                           class="w-full text-center text-2xl font-mono bg-gray-800 border border-gray-600 rounded-xl py-4 pr-12 focus:ring-primary-500 focus:border-primary-500 text-white placeholder-gray-500">
                    <button x-show="dialNumber" 
                            @click="dialNumber = dialNumber.slice(0, -1)" 
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white p-1">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Dial Pad -->
                <div class="grid grid-cols-3 gap-2 mb-4 flex-1">
                    <template x-for="key in dialpadKeys" :key="key.main">
                        <button @click="addDigit(key.main)" 
                                class="flex flex-col items-center justify-center bg-gray-800 hover:bg-gray-700 active:bg-gray-600 rounded-xl transition-all duration-150 active:scale-95 min-h-[60px]">
                            <span class="text-2xl font-medium text-white" x-text="key.main"></span>
                            <span class="text-[10px] text-gray-500 tracking-widest" x-text="key.sub" x-show="key.sub"></span>
                        </button>
                    </template>
                </div>

                <!-- Call Button -->
                <button @click="makeCall()" 
                        :disabled="!dialNumber || !isRegistered"
                        :class="{ 'opacity-50 cursor-not-allowed': !dialNumber || !isRegistered }"
                        class="w-full bg-green-600 hover:bg-green-500 text-white py-4 rounded-xl font-semibold flex items-center justify-center space-x-2 transition-all active:scale-[0.98]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <span>Call</span>
                </button>
            </div>

            <!-- Calling State -->
            <div x-show="callState === 'calling'" class="flex-1 flex flex-col items-center justify-center">
                <div class="w-24 h-24 bg-blue-500/20 rounded-full flex items-center justify-center mb-6 animate-pulse">
                    <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <p class="text-gray-400 mb-2">Calling</p>
                <p class="text-2xl font-mono text-white mb-8" x-text="callerNumber"></p>
                
                <button @click="hangUp()" 
                        class="w-full max-w-xs bg-red-600 hover:bg-red-500 text-white py-4 rounded-xl font-semibold flex items-center justify-center space-x-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z"/>
                    </svg>
                    <span>Cancel</span>
                </button>
            </div>

            <!-- Connected State -->
            <div x-show="callState === 'connected'" class="flex-1 flex flex-col">
                <div class="flex-1 flex flex-col items-center justify-center">
                    <div class="w-20 h-20 bg-green-500/20 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <p class="text-lg text-white mb-1" x-text="callerName || callerNumber"></p>
                    <p class="text-3xl font-mono text-green-400" x-text="callDuration"></p>
                </div>

                <!-- In-Call Controls -->
                <div class="grid grid-cols-4 gap-3 mb-4">
                    <button @click="toggleMute()" 
                            :class="{ 'bg-red-500/20 text-red-400': isMuted, 'bg-gray-800 text-gray-300': !isMuted }"
                            class="p-4 rounded-xl hover:bg-gray-700 transition-colors flex flex-col items-center">
                        <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="!isMuted" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                            <path x-show="isMuted" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                        </svg>
                        <span class="text-xs">Mute</span>
                    </button>
                    <button @click="toggleHold()"
                            :class="{ 'bg-yellow-500/20 text-yellow-400': isOnHold, 'bg-gray-800 text-gray-300': !isOnHold }"
                            class="p-4 rounded-xl hover:bg-gray-700 transition-colors flex flex-col items-center">
                        <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-xs">Hold</span>
                    </button>
                    <button @click="showKeypad = !showKeypad" 
                            :class="{ 'bg-primary-500/20 text-primary-400': showKeypad }"
                            class="p-4 rounded-xl bg-gray-800 text-gray-300 hover:bg-gray-700 transition-colors flex flex-col items-center">
                        <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        <span class="text-xs">Keypad</span>
                    </button>
                    <button @click="showTransfer = !showTransfer" 
                            :class="{ 'bg-primary-500/20 text-primary-400': showTransfer }"
                            class="p-4 rounded-xl bg-gray-800 text-gray-300 hover:bg-gray-700 transition-colors flex flex-col items-center">
                        <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        <span class="text-xs">Transfer</span>
                    </button>
                </div>

                <!-- In-call Keypad -->
                <div x-show="showKeypad" x-transition class="mb-4">
                    <div class="grid grid-cols-3 gap-2">
                        <template x-for="key in ['1', '2', '3', '4', '5', '6', '7', '8', '9', '*', '0', '#']" :key="key">
                            <button @click="sendDTMF(key)" 
                                    class="py-3 text-lg font-medium bg-gray-800 hover:bg-gray-700 rounded-lg transition-colors text-white"
                                    x-text="key">
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Transfer Input -->
                <div x-show="showTransfer" x-transition class="mb-4">
                    <div class="flex space-x-2">
                        <input type="text" 
                               x-model="transferNumber" 
                               placeholder="Transfer to..."
                               class="flex-1 bg-gray-800 border border-gray-600 rounded-lg py-3 px-4 text-white focus:ring-primary-500 focus:border-primary-500">
                        <button @click="transfer()" 
                                :disabled="!transferNumber"
                                class="px-6 py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-lg disabled:opacity-50">
                            Go
                        </button>
                    </div>
                </div>

                <!-- Hang Up Button -->
                <button @click="hangUp()" 
                        class="w-full bg-red-600 hover:bg-red-500 text-white py-4 rounded-xl font-semibold flex items-center justify-center space-x-2 transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z"/>
                    </svg>
                    <span>End Call</span>
                </button>
            </div>

            <!-- Ringing State (Incoming) -->
            <div x-show="callState === 'ringing'" class="flex-1 flex flex-col items-center justify-center">
                <div class="w-24 h-24 bg-yellow-500/20 rounded-full flex items-center justify-center mb-6 animate-pulse">
                    <svg class="w-12 h-12 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <p class="text-gray-400 mb-2">Incoming Call</p>
                <p class="text-sm text-gray-500 mb-1" x-text="callerName" x-show="callerName"></p>
                <p class="text-2xl font-mono text-white mb-8" x-text="callerNumber"></p>
                
                <div class="flex space-x-4 w-full max-w-xs">
                    <button @click="hangUp()" 
                            class="flex-1 bg-red-600 hover:bg-red-500 text-white py-4 rounded-xl font-semibold flex flex-col items-center">
                        <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span>Decline</span>
                    </button>
                    <button @click="answerCall()" 
                            class="flex-1 bg-green-600 hover:bg-green-500 text-white py-4 rounded-xl font-semibold flex flex-col items-center">
                        <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Answer</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function softphoneApp() {
        return {
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
            
            dialpadKeys: [
                { main: '1', sub: '' },
                { main: '2', sub: 'ABC' },
                { main: '3', sub: 'DEF' },
                { main: '4', sub: 'GHI' },
                { main: '5', sub: 'JKL' },
                { main: '6', sub: 'MNO' },
                { main: '7', sub: 'PQRS' },
                { main: '8', sub: 'TUV' },
                { main: '9', sub: 'WXYZ' },
                { main: '*', sub: '' },
                { main: '0', sub: '+' },
                { main: '#', sub: '' },
            ],
            
            init() {
                // Listen for webphone events
                window.addEventListener('webphone:statechange', (e) => this.handleStateChange(e.detail));
                window.addEventListener('webphone:error', (e) => this.handleError(e.detail));
                
                // Request notification permission
                if ('Notification' in window && Notification.permission === 'default') {
                    Notification.requestPermission();
                }
            },
            
            handleStateChange(detail) {
                console.log('Softphone state change:', detail);
                
                if (detail.state) {
                    this.callState = detail.state;
                    
                    if (detail.state === 'registered') {
                        this.isRegistered = true;
                        this.errorMessage = '';
                    } else if (detail.state === 'unregistered' || detail.state === 'disconnected') {
                        this.isRegistered = false;
                    } else if (detail.state === 'idle') {
                        this.resetCallState();
                    }
                }
                
                if (detail.number) this.callerNumber = detail.number;
                if (detail.name) this.callerName = detail.name;
                if (detail.callDuration) this.callDuration = detail.callDuration;
                if (typeof detail.isOnHold !== 'undefined') this.isOnHold = detail.isOnHold;
            },
            
            handleError(detail) {
                this.errorMessage = detail.message || 'An error occurred';
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
                    // Broadcast to main window
                    if (window.phoneSync) {
                        window.phoneSync.broadcast({ isMuted: this.isMuted });
                    }
                }
            },
            
            toggleHold() {
                if (window.webPhone) {
                    this.isOnHold = !this.isOnHold;
                    window.webPhone.hold(this.isOnHold);
                    // Broadcast to main window
                    if (window.phoneSync) {
                        window.phoneSync.broadcast({ isOnHold: this.isOnHold });
                    }
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
            }
        }
    }
    </script>
</body>
</html>

