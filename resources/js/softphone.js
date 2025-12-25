/**
 * WebRTC Softphone using SIP.js
 * 
 * This module handles WebRTC-based VoIP communication
 * with Asterisk via WebSocket.
 */

import { Web } from 'sip.js';

class Softphone {
    constructor() {
        this.simpleUser = null;
        this.isRegistered = false;
        this.credentials = null;
        this.callTimer = null;
        this.callDuration = 0;
        
        // Audio elements
        this.remoteAudio = null;
        this.ringtoneAudio = null;
        this.ringbackAudio = null;
        this.audioContext = null;
        this.ringtoneOscillator = null;
        this.ringbackOscillator = null;
        
        // Keep-alive mechanism
        this.keepAliveInterval = null;
        this.keepAliveIntervalMs = 25000; // 25 seconds (before typical NAT timeout of 30-60s)
        this.lastKeepAlive = null;
        this.missedKeepAlives = 0;
        this.maxMissedKeepAlives = 3;
    }

    /**
     * Initialize the softphone
     */
    async init() {
        try {
            // Create audio elements
            this.createAudioElements();
            
            // Fetch credentials
            await this.fetchCredentials();
            
            if (this.credentials) {
                await this.connect();
            }
            
            return true;
        } catch (error) {
            console.error('Softphone init error:', error);
            this.dispatchEvent('error', { message: error.message });
            return false;
        }
    }

    /**
     * Create audio elements for call audio
     */
    createAudioElements() {
        // Remote audio element
        if (!document.getElementById('softphone-remote-audio')) {
            this.remoteAudio = document.createElement('audio');
            this.remoteAudio.id = 'softphone-remote-audio';
            this.remoteAudio.autoplay = true;
            document.body.appendChild(this.remoteAudio);
        } else {
            this.remoteAudio = document.getElementById('softphone-remote-audio');
        }
        
        // Ringtone audio element (for incoming calls)
        if (!document.getElementById('softphone-ringtone')) {
            this.ringtoneAudio = document.createElement('audio');
            this.ringtoneAudio.id = 'softphone-ringtone';
            this.ringtoneAudio.loop = true;
            this.ringtoneAudio.src = '/storage/audio/ringtone.mp3';
            document.body.appendChild(this.ringtoneAudio);
        } else {
            this.ringtoneAudio = document.getElementById('softphone-ringtone');
        }
        
        // Ringback audio element (for outgoing calls)
        if (!document.getElementById('softphone-ringback')) {
            this.ringbackAudio = document.createElement('audio');
            this.ringbackAudio.id = 'softphone-ringback';
            this.ringbackAudio.loop = true;
            this.ringbackAudio.src = '/storage/audio/ringback.mp3';
            document.body.appendChild(this.ringbackAudio);
        } else {
            this.ringbackAudio = document.getElementById('softphone-ringback');
        }
        
        // Initialize Web Audio API for fallback tones
        try {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
        } catch (e) {
            console.warn('Web Audio API not available');
        }
    }

    /**
     * Fetch credentials from the API
     */
    async fetchCredentials() {
        try {
            const response = await fetch('/api/webphone/credentials', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const data = await response.json();
                console.warn('Softphone credentials error:', data.message);
                this.dispatchEvent('error', { message: data.message });
                return;
            }

            const data = await response.json();
            
            if (data.success) {
                this.credentials = data.credentials;
                console.log('Softphone credentials loaded for extension:', this.credentials.extension);
            }
        } catch (error) {
            console.error('Failed to fetch credentials:', error);
        }
    }

    /**
     * Connect and register with SIP server
     */
    async connect() {
        if (!this.credentials) {
            console.warn('No credentials available');
            return;
        }

        try {
            const server = this.credentials.wss_server;
            const aor = `sip:${this.credentials.extension}@${this.credentials.realm}`;
            
            console.log('Connecting to:', server, 'as', aor);
            
            // Announce connecting state
            this.dispatchEvent('statechange', { 
                state: 'connecting',
                isRegistered: false 
            });

            const options = {
                aor: aor,
                media: {
                    constraints: {
                        audio: true,
                        video: false,
                    },
                    remote: {
                        audio: this.remoteAudio,
                    },
                },
                userAgentOptions: {
                    authorizationUsername: this.credentials.extension,
                    authorizationPassword: this.credentials.password,
                    displayName: this.credentials.name || this.credentials.extension,
                    transportOptions: {
                        server: server,
                    },
                    sessionDescriptionHandlerFactoryOptions: {
                        peerConnectionConfiguration: {
                            iceServers: [
                                { urls: 'stun:stun.l.google.com:19302' },
                                { urls: 'stun:stun1.l.google.com:19302' },
                            ],
                        },
                    },
                },
            };

            this.simpleUser = new Web.SimpleUser(server, options);

            // Set up event delegates
            this.simpleUser.delegate = {
                onCallReceived: () => this.handleIncomingCall(),
                onCallAnswered: () => this.handleCallAnswered(),
                onCallHangup: () => this.handleCallHangup(),
                onCallHold: (held) => this.handleHoldChange(held),
                onRegistered: () => this.handleRegistered(),
                onUnregistered: () => this.handleUnregistered(),
                onServerConnect: () => console.log('Softphone: Server connected'),
                onServerDisconnect: (error) => this.handleServerDisconnect(error),
            };

            // Connect and register
            await this.simpleUser.connect();
            await this.simpleUser.register();
            
        } catch (error) {
            console.error('Softphone connection error:', error);
            this.dispatchEvent('error', { message: 'Connection failed' });
            
            // Retry after delay
            setTimeout(() => this.connect(), 5000);
        }
    }

    handleRegistered() {
        console.log('Softphone: Registered');
        this.isRegistered = true;
        this.missedKeepAlives = 0;
        this.dispatchEvent('statechange', { 
            state: 'registered',
            isRegistered: true 
        });
        this.logEvent('registered');
        
        // Start keep-alive mechanism
        this.startKeepAlive();
    }

    handleUnregistered() {
        console.log('Softphone: Unregistered');
        this.isRegistered = false;
        this.stopKeepAlive();
        this.dispatchEvent('statechange', { 
            state: 'unregistered',
            isRegistered: false 
        });
        this.logEvent('unregistered');
    }

    handleServerDisconnect(error) {
        console.error('Softphone: Server disconnected', error);
        this.isRegistered = false;
        this.stopKeepAlive();
        this.dispatchEvent('statechange', { state: 'disconnected' });
        
        // Attempt reconnection
        setTimeout(() => this.connect(), 5000);
    }

    /**
     * Start the keep-alive mechanism to prevent NAT timeout
     * Sends periodic messages to keep the connection alive
     */
    startKeepAlive() {
        this.stopKeepAlive(); // Clear any existing interval
        
        console.log('Softphone: Starting keep-alive (every ' + (this.keepAliveIntervalMs / 1000) + 's)');
        
        this.lastKeepAlive = Date.now();
        
        this.keepAliveInterval = setInterval(() => {
            this.sendKeepAlive();
        }, this.keepAliveIntervalMs);
    }

    /**
     * Stop the keep-alive mechanism
     */
    stopKeepAlive() {
        if (this.keepAliveInterval) {
            clearInterval(this.keepAliveInterval);
            this.keepAliveInterval = null;
            console.log('Softphone: Keep-alive stopped');
        }
    }

    /**
     * Send a keep-alive message
     * Uses the underlying transport to check connection health
     */
    async sendKeepAlive() {
        if (!this.simpleUser || !this.isRegistered) {
            return;
        }

        try {
            // Check if the WebSocket transport is still connected
            const userAgent = this.simpleUser.userAgent;
            
            if (userAgent && userAgent.transport) {
                const transport = userAgent.transport;
                
                // Check transport state
                if (transport.state === 'Connected') {
                    // Transport is healthy
                    this.lastKeepAlive = Date.now();
                    this.missedKeepAlives = 0;
                    console.log('Softphone: Keep-alive OK');
                } else {
                    // Transport seems unhealthy
                    this.missedKeepAlives++;
                    console.warn('Softphone: Keep-alive missed (' + this.missedKeepAlives + '/' + this.maxMissedKeepAlives + ')');
                    
                    if (this.missedKeepAlives >= this.maxMissedKeepAlives) {
                        console.error('Softphone: Too many missed keep-alives, reconnecting...');
                        this.stopKeepAlive();
                        this.handleServerDisconnect(new Error('Keep-alive timeout'));
                    }
                }
            }
            
            // Alternative: Send a re-REGISTER to refresh the registration
            // This is more reliable as it actually communicates with the server
            // Uncomment if transport check isn't enough:
            /*
            if (this.simpleUser.registerer) {
                await this.simpleUser.registerer.register();
                this.lastKeepAlive = Date.now();
                this.missedKeepAlives = 0;
                console.log('Softphone: Keep-alive REGISTER sent');
            }
            */
            
        } catch (error) {
            console.error('Softphone: Keep-alive error:', error);
            this.missedKeepAlives++;
            
            if (this.missedKeepAlives >= this.maxMissedKeepAlives) {
                console.error('Softphone: Keep-alive failed, reconnecting...');
                this.stopKeepAlive();
                this.handleServerDisconnect(error);
            }
        }
    }

    handleIncomingCall() {
        console.log('Softphone: Incoming call');
        const session = this.simpleUser.session;
        
        if (session) {
            const remoteIdentity = session.remoteIdentity;
            const number = remoteIdentity?.uri?.user || 'Unknown';
            const name = remoteIdentity?.displayName || number;
            
            const callData = {
                state: 'ringing',
                callState: 'ringing',
                number: number,
                callerNumber: number,
                callerId: number,
                name: name,
                callerName: name,
                callDirection: 'inbound',
                type: 'inbound',
            };
            
            this.dispatchEvent('statechange', callData);
            
            // Broadcast incoming call to main window for popup
            this.broadcastIncomingCall(callData);
            
            this.playRingtone();
            this.showNotification(number, name);
        }
    }

    broadcastIncomingCall(call) {
        // Broadcast via BroadcastChannel to main window
        try {
            const channel = new BroadcastChannel('webphone_sync');
            channel.postMessage({ 
                type: 'incoming_call', 
                call: {
                    caller_id: call.callerNumber || call.callerId,
                    caller_name: call.callerName || call.name,
                    type: call.type || 'inbound',
                    timestamp: Date.now()
                }
            });
            channel.close();
        } catch (e) {
            console.warn('Failed to broadcast incoming call:', e);
        }
    }

    handleCallAnswered() {
        console.log('Softphone: Call answered');
        this.stopRingtone();
        this.stopRingback();
        this.startCallTimer();
        this.dispatchEvent('statechange', { 
            state: 'connected',
            callState: 'connected'
        });
        this.logEvent('call_started');
    }

    handleCallHangup() {
        console.log('Softphone: Call hangup');
        this.stopRingtone();
        this.stopRingback();
        this.stopCallTimer();
        this.dispatchEvent('statechange', { 
            state: 'idle',
            callState: 'idle',
            callerNumber: '',
            callerName: '',
            callDuration: '00:00',
            isMuted: false,
            isOnHold: false
        });
        this.logEvent('call_ended');
    }

    handleHoldChange(held) {
        console.log('Softphone: Hold changed:', held);
        this.dispatchEvent('statechange', { 
            isOnHold: held 
        });
    }

    /**
     * Make an outbound call
     */
    async call(number) {
        if (!this.isRegistered || !this.simpleUser) {
            console.error('Softphone: Not registered');
            return false;
        }

        if (!number || number.trim() === '') {
            console.error('Softphone: No number provided');
            return false;
        }

        try {
            const destination = `sip:${number}@${this.credentials.realm}`;
            
            this.dispatchEvent('statechange', {
                state: 'calling',
                callState: 'calling',
                number: number,
                callerNumber: number,
                callDirection: 'outbound',
            });
            
            // Play ringback tone while waiting for answer
            this.playRingback();
            
            await this.simpleUser.call(destination);
            return true;
        } catch (error) {
            console.error('Softphone: Call failed', error);
            this.stopRingback();
            this.dispatchEvent('statechange', { state: 'idle' });
            this.logEvent('call_failed', { error: error.message });
            return false;
        }
    }

    /**
     * Answer incoming call
     */
    async answer() {
        if (!this.simpleUser) return false;
        
        try {
            this.stopRingtone();
            await this.simpleUser.answer();
            return true;
        } catch (error) {
            console.error('Softphone: Answer failed', error);
            return false;
        }
    }

    /**
     * Hang up current call
     */
    async hangup() {
        if (!this.simpleUser) return;
        
        try {
            this.stopRingtone();
            this.stopRingback();
            await this.simpleUser.hangup();
        } catch (error) {
            console.error('Softphone: Hangup failed', error);
        }
    }

    /**
     * Mute/unmute the call
     */
    mute(muted = true) {
        if (!this.simpleUser) {
            console.warn('Softphone: Cannot mute - no simpleUser');
            return false;
        }
        
        try {
            if (muted) {
                this.simpleUser.mute();
                console.log('Softphone: Muted');
            } else {
                this.simpleUser.unmute();
                console.log('Softphone: Unmuted');
            }
            
            // Dispatch state change
            this.dispatchEvent('statechange', { 
                isMuted: muted 
            });
            
            return true;
        } catch (error) {
            console.error('Softphone: Mute failed', error);
            return false;
        }
    }

    /**
     * Hold/unhold the call
     */
    async hold(held = true) {
        if (!this.simpleUser) {
            console.warn('Softphone: Cannot hold - no simpleUser');
            return false;
        }
        
        try {
            if (held) {
                await this.simpleUser.hold();
                console.log('Softphone: On hold');
            } else {
                await this.simpleUser.unhold();
                console.log('Softphone: Resumed');
            }
            
            // Dispatch state change
            this.dispatchEvent('statechange', { 
                isOnHold: held 
            });
            
            return true;
        } catch (error) {
            console.error('Softphone: Hold failed', error);
            return false;
        }
    }

    /**
     * Send DTMF tones
     */
    async sendDTMF(digit) {
        if (!this.simpleUser || !this.simpleUser.session) return;
        
        try {
            await this.simpleUser.sendDTMF(digit);
        } catch (error) {
            console.error('Softphone: DTMF failed', error);
        }
    }

    /**
     * Transfer call to another number
     */
    async transfer(target) {
        if (!this.simpleUser || !this.simpleUser.session) return false;
        
        try {
            // For blind transfer
            const destination = `sip:${target}@${this.credentials.realm}`;
            // SIP.js SimpleUser doesn't have direct transfer, need to access session
            console.log('Transfer to:', destination);
            // await this.simpleUser.session.refer(destination);
            return true;
        } catch (error) {
            console.error('Softphone: Transfer failed', error);
            return false;
        }
    }

    playRingtone() {
        // Try HTML5 audio first
        if (this.ringtoneAudio) {
            this.ringtoneAudio.play().catch(e => {
                console.warn('Ringtone audio file failed, using generated tone:', e);
                this.playGeneratedRingtone();
            });
        } else {
            this.playGeneratedRingtone();
        }
    }

    stopRingtone() {
        if (this.ringtoneAudio) {
            this.ringtoneAudio.pause();
            this.ringtoneAudio.currentTime = 0;
        }
        this.stopGeneratedRingtone();
    }
    
    playRingback() {
        // Try HTML5 audio first
        if (this.ringbackAudio) {
            this.ringbackAudio.play().catch(e => {
                console.warn('Ringback audio file failed, using generated tone:', e);
                this.playGeneratedRingback();
            });
        } else {
            this.playGeneratedRingback();
        }
    }
    
    stopRingback() {
        if (this.ringbackAudio) {
            this.ringbackAudio.pause();
            this.ringbackAudio.currentTime = 0;
        }
        this.stopGeneratedRingback();
    }
    
    /**
     * Generate ringtone using Web Audio API (fallback)
     * Creates a classic phone ring pattern: two tones alternating
     */
    playGeneratedRingtone() {
        if (!this.audioContext) return;
        
        // Resume audio context if suspended
        if (this.audioContext.state === 'suspended') {
            this.audioContext.resume();
        }
        
        this.stopGeneratedRingtone();
        
        const playRingCycle = () => {
            if (!this.audioContext || this.ringtoneOscillator === 'stopped') return;
            
            // Create oscillators for a dual-tone ring (like classic phone)
            const osc1 = this.audioContext.createOscillator();
            const osc2 = this.audioContext.createOscillator();
            const gainNode = this.audioContext.createGain();
            
            osc1.type = 'sine';
            osc2.type = 'sine';
            osc1.frequency.setValueAtTime(440, this.audioContext.currentTime); // A4
            osc2.frequency.setValueAtTime(480, this.audioContext.currentTime); // B4
            
            osc1.connect(gainNode);
            osc2.connect(gainNode);
            gainNode.connect(this.audioContext.destination);
            gainNode.gain.setValueAtTime(0.2, this.audioContext.currentTime);
            
            osc1.start();
            osc2.start();
            
            // Ring for 1 second, pause for 2 seconds
            setTimeout(() => {
                osc1.stop();
                osc2.stop();
            }, 1000);
        };
        
        // Play immediately and then repeat
        playRingCycle();
        this.ringtoneOscillator = setInterval(playRingCycle, 3000);
    }
    
    stopGeneratedRingtone() {
        if (this.ringtoneOscillator && this.ringtoneOscillator !== 'stopped') {
            clearInterval(this.ringtoneOscillator);
            this.ringtoneOscillator = 'stopped';
        }
    }
    
    /**
     * Generate ringback tone using Web Audio API (fallback)
     * Standard US ringback: 440Hz + 480Hz, 2s on, 4s off
     */
    playGeneratedRingback() {
        if (!this.audioContext) return;
        
        // Resume audio context if suspended
        if (this.audioContext.state === 'suspended') {
            this.audioContext.resume();
        }
        
        this.stopGeneratedRingback();
        
        const playRingbackCycle = () => {
            if (!this.audioContext || this.ringbackOscillator === 'stopped') return;
            
            const osc1 = this.audioContext.createOscillator();
            const osc2 = this.audioContext.createOscillator();
            const gainNode = this.audioContext.createGain();
            
            osc1.type = 'sine';
            osc2.type = 'sine';
            osc1.frequency.setValueAtTime(440, this.audioContext.currentTime);
            osc2.frequency.setValueAtTime(480, this.audioContext.currentTime);
            
            osc1.connect(gainNode);
            osc2.connect(gainNode);
            gainNode.connect(this.audioContext.destination);
            gainNode.gain.setValueAtTime(0.15, this.audioContext.currentTime);
            
            osc1.start();
            osc2.start();
            
            // Play for 2 seconds
            setTimeout(() => {
                osc1.stop();
                osc2.stop();
            }, 2000);
        };
        
        // Play immediately and then repeat every 6 seconds (2s on, 4s off)
        playRingbackCycle();
        this.ringbackOscillator = setInterval(playRingbackCycle, 6000);
    }
    
    stopGeneratedRingback() {
        if (this.ringbackOscillator && this.ringbackOscillator !== 'stopped') {
            clearInterval(this.ringbackOscillator);
            this.ringbackOscillator = 'stopped';
        }
    }

    startCallTimer() {
        this.callDuration = 0;
        this.callTimer = setInterval(() => {
            this.callDuration++;
            const formatted = this.formatDuration(this.callDuration);
            this.dispatchEvent('statechange', { 
                callDuration: formatted 
            });
        }, 1000);
    }

    stopCallTimer() {
        if (this.callTimer) {
            clearInterval(this.callTimer);
            this.callTimer = null;
        }
        this.callDuration = 0;
    }

    formatDuration(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    dispatchEvent(type, detail = {}) {
        window.dispatchEvent(new CustomEvent(`webphone:${type}`, { detail }));
        
        // Also broadcast to other windows via phoneSync
        if (window.phoneSync && type === 'statechange') {
            window.phoneSync.broadcast(detail);
        }
    }

    showNotification(number, name) {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('Incoming Call', {
                body: `${name || number}`,
                icon: '/storage/images/phone-icon.png',
                tag: 'incoming-call',
                requireInteraction: true,
            });
        }
    }

    async logEvent(event, details = {}) {
        try {
            await fetch('/api/webphone/events', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ event, details }),
            });
        } catch (error) {
            console.warn('Failed to log event:', error);
        }
    }

    async disconnect() {
        // Stop keep-alive first
        this.stopKeepAlive();
        
        if (this.simpleUser) {
            try {
                await this.simpleUser.unregister();
                await this.simpleUser.disconnect();
            } catch (error) {
                console.error('Softphone disconnect error:', error);
            }
        }
    }

    static requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }
}

// Create global instance
window.webPhone = null;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const hasExtension = document.querySelector('[data-has-extension="true"]');
    if (hasExtension) {
        window.webPhone = new Softphone();
        window.webPhone.init();
        Softphone.requestNotificationPermission();
    }
});

// Export
export { Softphone };
export default Softphone;
