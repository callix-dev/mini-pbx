<!-- Incoming Call Popup -->
<div x-data="incomingCallPopup()" 
     x-show="showPopup" 
     x-cloak
     @incoming-call.window="handleIncomingCall($event.detail)"
     class="fixed inset-0 z-[100] overflow-y-auto"
     aria-labelledby="incoming-call-title" 
     role="dialog" 
     aria-modal="true">
    
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-900/75 transition-opacity" 
         x-show="showPopup"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
    </div>

    <!-- Popup -->
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="showPopup"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-2xl transition-all sm:w-full sm:max-w-md">
                
                <!-- Animated Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-green-500 to-emerald-600 opacity-10 animate-pulse"></div>
                
                <!-- Content -->
                <div class="relative p-6">
                    <!-- Header -->
                    <div class="text-center mb-6">
                        <div class="mx-auto w-20 h-20 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center mb-4 animate-bounce shadow-lg">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <h3 id="incoming-call-title" class="text-2xl font-bold text-gray-900 dark:text-white">
                            Incoming Call
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            <span x-text="callType"></span>
                        </p>
                    </div>

                    <!-- Caller Info -->
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 mb-6">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Caller ID</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white font-mono" x-text="callerId"></span>
                        </div>
                        <div class="flex items-center justify-between mb-3" x-show="callerName">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Name</span>
                            <span class="text-lg font-semibold text-gray-900 dark:text-white" x-text="callerName"></span>
                        </div>
                        <div class="flex items-center justify-between" x-show="queueName">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Queue</span>
                            <span class="px-2 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-sm font-medium rounded" x-text="queueName"></span>
                        </div>
                        <div class="flex items-center justify-between mt-3" x-show="did">
                            <span class="text-sm text-gray-500 dark:text-gray-400">DID</span>
                            <span class="text-sm font-mono text-gray-700 dark:text-gray-300" x-text="did"></span>
                        </div>
                    </div>

                    <!-- Timer -->
                    <div class="text-center mb-6">
                        <span class="text-3xl font-mono font-bold text-gray-900 dark:text-white" x-text="timer"></span>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Ringing...</p>
                    </div>

                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-4">
                        <button @click="answerCall()" 
                                class="flex items-center justify-center px-6 py-4 bg-green-500 hover:bg-green-600 text-white font-semibold rounded-xl transition-all transform hover:scale-105 shadow-lg">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            Answer
                        </button>
                        <button @click="declineCall()" 
                                class="flex items-center justify-center px-6 py-4 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-xl transition-all transform hover:scale-105 shadow-lg">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Decline
                        </button>
                    </div>

                    <!-- Quick Actions -->
                    <div class="mt-4 flex justify-center space-x-4">
                        <button @click="sendToVoicemail()" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Send to Voicemail
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Audio for ringtone -->
    <audio x-ref="ringtone" loop preload="auto">
        <source src="{{ asset('storage/audio/ringtone.mp3') }}" type="audio/mpeg">
    </audio>
</div>

<script>
function incomingCallPopup() {
    return {
        showPopup: false,
        callerId: '',
        callerName: '',
        callType: 'Incoming Call',
        queueName: '',
        did: '',
        timer: '00:00',
        timerInterval: null,
        startTime: null,
        callData: null,

        handleIncomingCall(call) {
            this.callerId = call.caller_id || call.callerId || 'Unknown';
            this.callerName = call.caller_name || call.callerName || '';
            this.callType = call.type === 'queue' ? 'Queue Call' : (call.type === 'internal' ? 'Internal Call' : 'Incoming Call');
            this.queueName = call.queue_name || call.queueName || '';
            this.did = call.did || '';
            this.callData = call;
            this.showPopup = true;
            this.startTimer();
            this.playRingtone();

            // Request browser notification permission and show notification
            if (Notification.permission === 'granted') {
                new Notification('Incoming Call', {
                    body: `From: ${this.callerId}${this.callerName ? ' - ' + this.callerName : ''}`,
                    icon: '/favicon.ico',
                    tag: 'incoming-call',
                    requireInteraction: true
                });
            }
        },

        startTimer() {
            this.startTime = Date.now();
            this.timer = '00:00';
            this.timerInterval = setInterval(() => {
                const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
                const mins = Math.floor(elapsed / 60);
                const secs = elapsed % 60;
                this.timer = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            }, 1000);
        },

        stopTimer() {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }
        },

        playRingtone() {
            try {
                const audio = this.$refs.ringtone;
                if (audio) {
                    audio.currentTime = 0;
                    audio.play().catch(() => {});
                }
            } catch (e) {}
        },

        stopRingtone() {
            try {
                const audio = this.$refs.ringtone;
                if (audio) {
                    audio.pause();
                    audio.currentTime = 0;
                }
            } catch (e) {}
        },

        answerCall() {
            this.closePopup();
            // Notify the webphone to answer
            const channel = new BroadcastChannel('webphone_sync');
            channel.postMessage({ type: 'answer_call', call: this.callData });
            channel.close();
            
            // Focus on webphone popup if open
            if (window.phoneSync && window.phoneSync.popupWindow) {
                window.phoneSync.popupWindow.focus();
            }
        },

        declineCall() {
            this.closePopup();
            // Notify the webphone to decline
            const channel = new BroadcastChannel('webphone_sync');
            channel.postMessage({ type: 'decline_call', call: this.callData });
            channel.close();
        },

        sendToVoicemail() {
            this.closePopup();
            // Notify the webphone to send to voicemail
            const channel = new BroadcastChannel('webphone_sync');
            channel.postMessage({ type: 'voicemail_call', call: this.callData });
            channel.close();
        },

        closePopup() {
            this.showPopup = false;
            this.stopTimer();
            this.stopRingtone();
        }
    }
}
</script>

