<!-- WebRTC Softphone Panel -->
<div x-data="{ 
    isOpen: false, 
    isMinimized: false, 
    callState: 'idle',
    callDuration: '00:00',
    callerNumber: '',
    isMuted: false,
    isOnHold: false 
}" 
    class="fixed bottom-4 right-4 z-50">
    
    <!-- Softphone Toggle Button -->
    <button @click="isOpen = !isOpen; isMinimized = false" 
            class="bg-primary-600 hover:bg-primary-700 text-white rounded-full p-4 shadow-lg transition-all duration-200"
            :class="{ 'animate-pulse': callState === 'ringing' }">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
        </svg>
    </button>

    <!-- Softphone Panel -->
    <div x-show="isOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute bottom-16 right-0 w-80 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        
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
                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                <span class="text-gray-600 dark:text-gray-400">Connected</span>
            </div>
            <span class="text-gray-500 dark:text-gray-400">Ext: {{ auth()->user()->extension?->extension ?? '-' }}</span>
        </div>

        <!-- Main Content -->
        <div x-show="!isMinimized" class="p-4">
            <!-- Idle State - Dial Pad -->
            <div x-show="callState === 'idle'">
                <!-- Display -->
                <input type="text" id="dial-number" placeholder="Enter number..."
                       class="w-full text-center text-xl font-mono bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg py-3 mb-4 focus:ring-primary-500 focus:border-primary-500">
                
                <!-- Dial Pad -->
                <div class="grid grid-cols-3 gap-2 mb-4">
                    @foreach(['1', '2', '3', '4', '5', '6', '7', '8', '9', '*', '0', '#'] as $key)
                        <button onclick="addDigit('{{ $key }}')" 
                                class="py-3 text-lg font-medium bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                            {{ $key }}
                        </button>
                    @endforeach
                </div>

                <!-- Call Button -->
                <button onclick="makeCall()" 
                        class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-medium flex items-center justify-center space-x-2 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <span>Call</span>
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
                    <p class="text-lg font-medium text-gray-900 dark:text-white" x-text="callerNumber"></p>
                    <p class="text-2xl font-mono text-gray-600 dark:text-gray-400" x-text="callDuration"></p>
                </div>

                <!-- In-Call Controls -->
                <div class="grid grid-cols-4 gap-2 mb-4">
                    <button @click="isMuted = !isMuted" 
                            :class="{ 'bg-red-100 dark:bg-red-900/30': isMuted }"
                            class="p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <svg class="w-5 h-5 mx-auto" :class="{ 'text-red-600': isMuted }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                        </svg>
                        <span class="text-xs">Mute</span>
                    </button>
                    <button @click="isOnHold = !isOnHold"
                            :class="{ 'bg-yellow-100 dark:bg-yellow-900/30': isOnHold }"
                            class="p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <svg class="w-5 h-5 mx-auto" :class="{ 'text-yellow-600': isOnHold }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-xs">Hold</span>
                    </button>
                    <button class="p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        <span class="text-xs">Transfer</span>
                    </button>
                    <button class="p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        <span class="text-xs">Keypad</span>
                    </button>
                </div>

                <!-- Hang Up Button -->
                <button onclick="hangUp()" 
                        class="w-full bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg font-medium flex items-center justify-center space-x-2 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z"/>
                    </svg>
                    <span>End Call</span>
                </button>
            </div>

            <!-- Ringing State -->
            <div x-show="callState === 'ringing'" class="text-center">
                <div class="mb-4">
                    <div class="w-16 h-16 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center mx-auto mb-3 animate-pulse">
                        <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <p class="text-lg font-medium text-gray-900 dark:text-white">Incoming Call</p>
                    <p class="text-xl font-mono text-gray-600 dark:text-gray-400" x-text="callerNumber"></p>
                </div>

                <div class="flex space-x-3">
                    <button onclick="hangUp()" 
                            class="flex-1 bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg font-medium">
                        Decline
                    </button>
                    <button onclick="answerCall()" 
                            class="flex-1 bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-medium">
                        Answer
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function addDigit(digit) {
        const input = document.getElementById('dial-number');
        input.value += digit;
    }

    function makeCall() {
        const number = document.getElementById('dial-number').value;
        if (number) {
            console.log('Calling:', number);
            // TODO: Implement WebRTC call
        }
    }

    function hangUp() {
        console.log('Hanging up');
        // TODO: Implement WebRTC hangup
    }

    function answerCall() {
        console.log('Answering call');
        // TODO: Implement WebRTC answer
    }
</script>
@endpush
