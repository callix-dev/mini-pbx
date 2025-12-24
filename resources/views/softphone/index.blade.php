<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Softphone - {{ config('app.name') }}</title>
    
    <!-- Critical CSS to prevent flash -->
    <style>
        html, body { background-color: #111827; }
        body { overflow: hidden; }
    </style>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
                <!-- Mic permission indicator -->
                <span x-show="micPermission === 'granted'" class="text-green-400" title="Microphone enabled">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                    </svg>
                </span>
                <span x-show="micPermission === 'denied'" class="text-red-400" title="Microphone blocked">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                    </svg>
                </span>
                <span class="w-2 h-2 rounded-full" :class="isRegistered ? 'bg-green-400' : 'bg-red-400'"></span>
                <span class="text-xs" x-text="isRegistered ? 'Online' : 'Offline'"></span>
            </div>
        </div>

        <!-- Permission Request Banner -->
        <div x-show="micPermission === 'prompt'" 
             class="bg-blue-600 px-4 py-3 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center space-x-3 text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                </svg>
                <span class="text-sm">Microphone access required</span>
            </div>
            <button @click="requestMicPermission()" 
                    class="px-3 py-1 bg-white text-blue-600 rounded-lg text-sm font-medium hover:bg-blue-50">
                Allow
            </button>
        </div>

        <!-- Permission Denied Banner -->
        <div x-show="micPermission === 'denied'" 
             class="bg-red-600 px-4 py-3 flex-shrink-0">
            <div class="flex items-center space-x-3 text-white mb-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span class="text-sm font-medium">Microphone access blocked</span>
            </div>
            <p class="text-xs text-white/80">Click the lock/camera icon in your browser's address bar to enable microphone access, then refresh.</p>
        </div>

        <!-- Status Bar -->
        <div class="bg-gray-800 px-4 py-2 border-b border-gray-700 flex-shrink-0">
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center space-x-2">
                    <template x-if="callState === 'connecting'">
                        <span class="text-blue-400 flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Connecting...
                        </span>
                    </template>
                    <template x-if="callState === 'idle' || callState === 'registered'">
                        <span class="text-gray-400" x-text="micPermission === 'granted' ? 'Ready to make calls' : 'Waiting for mic permission...'"></span>
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
            
            <!-- Dial Pad (Always visible, but disabled when not registered) -->
            <div x-show="!isInCall" class="flex-1 flex flex-col" :class="{ 'opacity-60': !isRegistered }">
                <!-- Display -->
                <div class="relative mb-4">
                    <input type="text" 
                           x-model="dialNumber" 
                           x-ref="dialInput"
                           @keydown.enter="isRegistered && makeCall()"
                           :disabled="!isRegistered"
                           :placeholder="isRegistered ? 'Enter number...' : 'Waiting for connection...'"
                           class="w-full text-center text-2xl font-mono bg-gray-800 border border-gray-600 rounded-xl py-4 pr-12 focus:ring-primary-500 focus:border-primary-500 text-white placeholder-gray-500 disabled:cursor-not-allowed disabled:opacity-70">
                    <button x-show="dialNumber && isRegistered" 
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
                        <button @click="isRegistered && addDigit(key.main)" 
                                :disabled="!isRegistered"
                                :class="isRegistered 
                                    ? 'bg-gray-800 hover:bg-gray-700 active:bg-gray-600 active:scale-95' 
                                    : 'bg-gray-800/50 cursor-not-allowed'"
                                class="flex flex-col items-center justify-center rounded-xl transition-all duration-150 min-h-[60px]">
                            <span class="text-2xl font-medium" :class="isRegistered ? 'text-white' : 'text-gray-500'" x-text="key.main"></span>
                            <span class="text-[10px] tracking-widest" :class="isRegistered ? 'text-gray-500' : 'text-gray-600'" x-text="key.sub" x-show="key.sub"></span>
                        </button>
                    </template>
                </div>

                <!-- Call Button -->
                <button @click="makeCall()" 
                        :disabled="!dialNumber || !isRegistered"
                        :class="!isRegistered 
                            ? 'bg-gray-600 cursor-not-allowed' 
                            : (!dialNumber ? 'opacity-50 cursor-not-allowed bg-green-600' : 'bg-green-600 hover:bg-green-500')"
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
            micPermission: 'prompt', // 'prompt', 'granted', 'denied'
            connectingAnnouncementInterval: null,
            
            get isInCall() {
                return ['ringing', 'calling', 'connected'].includes(this.callState);
            },
            
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
            
            async init() {
                // Listen for webphone events
                window.addEventListener('webphone:statechange', (e) => this.handleStateChange(e.detail));
                window.addEventListener('webphone:error', (e) => this.handleError(e.detail));
                
                // Start heartbeat to let main window know we're open
                this.startHeartbeat();
                
                // Listen for logout from main window
                this.listenForLogout();
                
                // Check and request permissions
                await this.checkMicPermission();
                
                // Request notification permission
                if ('Notification' in window && Notification.permission === 'default') {
                    Notification.requestPermission();
                }
                
                // Clean up heartbeat when window closes
                window.addEventListener('beforeunload', () => {
                    localStorage.removeItem('mini-pbx-phone-heartbeat');
                });
            },
            
            startHeartbeat() {
                // Send heartbeat immediately
                localStorage.setItem('mini-pbx-phone-heartbeat', Date.now().toString());
                
                // Send heartbeat every second
                setInterval(() => {
                    localStorage.setItem('mini-pbx-phone-heartbeat', Date.now().toString());
                }, 1000);
            },
            
            listenForLogout() {
                // Listen for logout event from main window
                if (window.phoneSync) {
                    window.phoneSync.onLogout(() => {
                        this.handleLogout();
                    });
                }
                
                // Also listen via localStorage directly as backup
                window.addEventListener('storage', (event) => {
                    if (event.key === 'mini-pbx-phone-logout') {
                        this.handleLogout();
                    }
                });
            },
            
            handleLogout() {
                console.log('Softphone: Logout detected, closing...');
                
                // Announce logout
                this.speak('Logging out');
                
                // Deregister the phone
                if (window.webPhone) {
                    window.webPhone.disconnect();
                }
                
                // Clear heartbeat
                localStorage.removeItem('mini-pbx-phone-heartbeat');
                
                // Close the window after a short delay (let deregistration complete)
                setTimeout(() => {
                    window.close();
                }, 1500);
            },
            
            // Text-to-Speech for status announcements
            speak(message) {
                if ('speechSynthesis' in window) {
                    // Cancel any ongoing speech
                    window.speechSynthesis.cancel();
                    
                    const utterance = new SpeechSynthesisUtterance(message);
                    utterance.rate = 1.0;
                    utterance.pitch = 1.0;
                    utterance.volume = 0.8;
                    
                    // Try to use a good voice
                    const voices = window.speechSynthesis.getVoices();
                    const preferredVoice = voices.find(v => 
                        v.name.includes('Google') || 
                        v.name.includes('Samantha') || 
                        v.name.includes('Microsoft') ||
                        v.lang.startsWith('en')
                    );
                    if (preferredVoice) {
                        utterance.voice = preferredVoice;
                    }
                    
                    window.speechSynthesis.speak(utterance);
                }
            },
            
            announceRegistering() {
                // Clear any existing interval
                this.stopConnectingAnnouncement();
                
                // Announce immediately
                this.speak('Trying to register phone');
                
                // Repeat every 5 seconds until connected
                this.connectingAnnouncementInterval = setInterval(() => {
                    if (this.callState === 'connecting' && !this.isRegistered) {
                        this.speak('Trying to register phone');
                    } else {
                        this.stopConnectingAnnouncement();
                    }
                }, 5000);
            },
            
            stopConnectingAnnouncement() {
                if (this.connectingAnnouncementInterval) {
                    clearInterval(this.connectingAnnouncementInterval);
                    this.connectingAnnouncementInterval = null;
                }
            },
            
            announceRegistered() {
                this.stopConnectingAnnouncement();
                this.speak('Your phone is connected successfully');
            },
            
            announceRegistrationFailed() {
                this.stopConnectingAnnouncement();
                this.speak('Phone registration failed');
            },
            
            async checkMicPermission() {
                try {
                    // Check current permission status
                    if (navigator.permissions) {
                        const result = await navigator.permissions.query({ name: 'microphone' });
                        this.micPermission = result.state;
                        
                        // Listen for permission changes
                        result.onchange = () => {
                            this.micPermission = result.state;
                            if (result.state === 'granted') {
                                // Re-initialize webphone if permission granted
                                if (window.webPhone && !window.webPhone.isRegistered) {
                                    window.webPhone.connect();
                                }
                            }
                        };
                        
                        // If already granted, we're good
                        if (result.state === 'granted') {
                            return;
                        }
                        
                        // If prompt, request permission immediately
                        if (result.state === 'prompt') {
                            await this.requestMicPermission();
                        }
                    } else {
                        // Fallback: try requesting directly
                        await this.requestMicPermission();
                    }
                } catch (error) {
                    console.error('Permission check error:', error);
                    // Fallback: try requesting
                    await this.requestMicPermission();
                }
            },
            
            async requestMicPermission() {
                try {
                    // Request microphone access
                    const stream = await navigator.mediaDevices.getUserMedia({ 
                        audio: {
                            echoCancellation: true,
                            noiseSuppression: true,
                            autoGainControl: true
                        } 
                    });
                    
                    // Permission granted - stop the test stream
                    stream.getTracks().forEach(track => track.stop());
                    
                    this.micPermission = 'granted';
                    console.log('Microphone permission granted');
                    
                    // Initialize webphone now that we have permission
                    if (window.webPhone && !window.webPhone.isRegistered) {
                        window.webPhone.connect();
                    }
                } catch (error) {
                    console.error('Microphone permission error:', error);
                    
                    if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                        this.micPermission = 'denied';
                    } else if (error.name === 'NotFoundError') {
                        this.errorMessage = 'No microphone found on this device';
                        this.micPermission = 'denied';
                    } else {
                        this.errorMessage = 'Could not access microphone: ' + error.message;
                    }
                }
            },
            
            handleStateChange(detail) {
                console.log('Softphone state change:', detail);
                
                if (detail.state) {
                    const previousState = this.callState;
                    const wasRegistered = this.isRegistered;
                    this.callState = detail.state;
                    
                    if (detail.state === 'connecting') {
                        // Announce attempting to register
                        this.announceRegistering();
                    } else if (detail.state === 'registered') {
                        this.isRegistered = true;
                        this.errorMessage = '';
                        // Announce successful registration (only if we weren't already registered)
                        if (!wasRegistered) {
                            this.announceRegistered();
                        }
                    } else if (detail.state === 'unregistered' || detail.state === 'disconnected') {
                        // Announce failure if we were trying to connect
                        if (wasRegistered || previousState === 'connecting') {
                            this.announceRegistrationFailed();
                        }
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
            
            // DTMF frequencies for each key
            dtmfFrequencies: {
                '1': [697, 1209], '2': [697, 1336], '3': [697, 1477],
                '4': [770, 1209], '5': [770, 1336], '6': [770, 1477],
                '7': [852, 1209], '8': [852, 1336], '9': [852, 1477],
                '*': [941, 1209], '0': [941, 1336], '#': [941, 1477]
            },
            audioContext: null,
            
            addDigit(digit) {
                this.dialNumber += digit;
                this.playDTMFTone(digit);
            },
            
            playDTMFTone(digit) {
                try {
                    // Create audio context if needed
                    if (!this.audioContext) {
                        this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    }
                    
                    const frequencies = this.dtmfFrequencies[digit];
                    if (!frequencies) return;
                    
                    const duration = 0.15; // 150ms tone
                    const now = this.audioContext.currentTime;
                    
                    // Create gain node for volume control
                    const gainNode = this.audioContext.createGain();
                    gainNode.connect(this.audioContext.destination);
                    gainNode.gain.setValueAtTime(0.3, now);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, now + duration);
                    
                    // Create two oscillators for the dual-tone
                    frequencies.forEach(freq => {
                        const oscillator = this.audioContext.createOscillator();
                        oscillator.type = 'sine';
                        oscillator.frequency.setValueAtTime(freq, now);
                        oscillator.connect(gainNode);
                        oscillator.start(now);
                        oscillator.stop(now + duration);
                    });
                } catch (e) {
                    console.warn('Could not play DTMF tone:', e);
                }
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
                    this.playDTMFTone(digit);
                }
            }
        }
    }
    </script>
</body>
</html>

