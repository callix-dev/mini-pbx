/**
 * WebPhone - SIP.js Integration for Mini-PBX
 * 
 * This module handles WebRTC-based phone functionality using SIP.js
 */

import { Web } from 'sip.js';

class WebPhone {
    constructor() {
        this.simpleUser = null;
        this.isRegistered = false;
        this.currentSession = null;
        this.callTimer = null;
        this.callDuration = 0;
        this.credentials = null;
        this.settings = null;
        
        // Audio elements
        this.remoteAudio = null;
        this.ringtoneAudio = null;
        
        // State callbacks (set by Alpine.js component)
        this.onStateChange = null;
        this.onCallStart = null;
        this.onCallEnd = null;
        this.onIncomingCall = null;
        this.onRegistrationChange = null;
        
        this.init();
    }

    async init() {
        // Create audio elements
        this.createAudioElements();
        
        // Fetch credentials from API
        try {
            await this.fetchCredentials();
            if (this.credentials) {
                await this.connect();
            }
        } catch (error) {
            console.error('WebPhone init error:', error);
        }
    }

    createAudioElements() {
        // Remote audio for call audio
        this.remoteAudio = document.createElement('audio');
        this.remoteAudio.id = 'webphone-remote-audio';
        this.remoteAudio.autoplay = true;
        document.body.appendChild(this.remoteAudio);
        
        // Ringtone audio
        this.ringtoneAudio = document.createElement('audio');
        this.ringtoneAudio.id = 'webphone-ringtone';
        this.ringtoneAudio.loop = true;
        this.ringtoneAudio.src = '/storage/audio/ringtone.mp3';
        document.body.appendChild(this.ringtoneAudio);
    }

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
                console.warn('WebPhone credentials error:', data.message);
                this.updateState('error', data.message);
                return;
            }

            const data = await response.json();
            
            if (data.success) {
                this.credentials = data.credentials;
                this.settings = data.settings || {};
                console.log('WebPhone credentials loaded for:', this.credentials.extension);
            }
        } catch (error) {
            console.error('Failed to fetch WebPhone credentials:', error);
        }
    }

    async connect() {
        if (!this.credentials) {
            console.warn('No credentials available');
            return;
        }

        try {
            const server = this.credentials.wss_server;
            const aor = `sip:${this.credentials.extension}@${this.credentials.realm}`;
            
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
                    uri: Web.UserAgent.makeURI(aor),
                    transportOptions: {
                        server: server,
                    },
                    sessionDescriptionHandlerFactoryOptions: {
                        peerConnectionConfiguration: {
                            iceServers: [
                                { urls: 'stun:stun.l.google.com:19302' },
                            ],
                        },
                    },
                },
            };

            this.simpleUser = new Web.SimpleUser(server, options);

            // Set up delegates for handling events
            this.simpleUser.delegate = {
                onCallReceived: () => this.handleIncomingCall(),
                onCallAnswered: () => this.handleCallAnswered(),
                onCallHangup: () => this.handleCallHangup(),
                onCallHold: (held) => this.handleHoldChange(held),
                onRegistered: () => this.handleRegistered(),
                onUnregistered: () => this.handleUnregistered(),
                onServerConnect: () => console.log('WebPhone: Server connected'),
                onServerDisconnect: (error) => this.handleServerDisconnect(error),
            };

            // Connect and register
            await this.simpleUser.connect();
            await this.simpleUser.register();
            
        } catch (error) {
            console.error('WebPhone connection error:', error);
            this.updateState('error', 'Connection failed');
            
            // Retry after delay
            setTimeout(() => this.connect(), 5000);
        }
    }

    handleRegistered() {
        console.log('WebPhone: Registered');
        this.isRegistered = true;
        this.updateState('registered');
        
        if (this.onRegistrationChange) {
            this.onRegistrationChange(true);
        }
        
        // Log event to server
        this.logEvent('registered');
    }

    handleUnregistered() {
        console.log('WebPhone: Unregistered');
        this.isRegistered = false;
        this.updateState('unregistered');
        
        if (this.onRegistrationChange) {
            this.onRegistrationChange(false);
        }
        
        this.logEvent('unregistered');
    }

    handleServerDisconnect(error) {
        console.error('WebPhone: Server disconnected', error);
        this.isRegistered = false;
        this.updateState('disconnected');
        
        // Attempt reconnection
        setTimeout(() => this.connect(), 5000);
    }

    handleIncomingCall() {
        console.log('WebPhone: Incoming call');
        const session = this.simpleUser.session;
        
        if (session) {
            const remoteIdentity = session.remoteIdentity;
            const callerNumber = remoteIdentity?.uri?.user || 'Unknown';
            const callerName = remoteIdentity?.displayName || callerNumber;
            
            this.updateState('ringing', {
                number: callerNumber,
                name: callerName,
                direction: 'inbound',
            });
            
            // Play ringtone
            this.playRingtone();
            
            // Show desktop notification if permitted
            this.showIncomingNotification(callerNumber, callerName);
            
            if (this.onIncomingCall) {
                this.onIncomingCall({ number: callerNumber, name: callerName });
            }
        }
    }

    handleCallAnswered() {
        console.log('WebPhone: Call answered');
        this.stopRingtone();
        this.startCallTimer();
        this.updateState('connected');
        
        if (this.onCallStart) {
            this.onCallStart();
        }
        
        this.logEvent('call_started');
    }

    handleCallHangup() {
        console.log('WebPhone: Call hangup');
        this.stopRingtone();
        this.stopCallTimer();
        this.updateState('idle');
        
        if (this.onCallEnd) {
            this.onCallEnd();
        }
        
        this.logEvent('call_ended');
    }

    handleHoldChange(held) {
        console.log('WebPhone: Hold state changed:', held);
        if (this.onStateChange) {
            this.onStateChange({ isOnHold: held });
        }
    }

    async call(number) {
        if (!this.isRegistered || !this.simpleUser) {
            console.error('WebPhone: Not registered');
            return false;
        }

        if (!number || number.trim() === '') {
            console.error('WebPhone: No number provided');
            return false;
        }

        try {
            const destination = `sip:${number}@${this.credentials.realm}`;
            
            this.updateState('calling', {
                number: number,
                direction: 'outbound',
            });
            
            await this.simpleUser.call(destination);
            return true;
        } catch (error) {
            console.error('WebPhone: Call failed', error);
            this.updateState('idle');
            this.logEvent('call_failed', { error: error.message });
            return false;
        }
    }

    async answer() {
        if (!this.simpleUser) return false;
        
        try {
            this.stopRingtone();
            await this.simpleUser.answer();
            return true;
        } catch (error) {
            console.error('WebPhone: Answer failed', error);
            return false;
        }
    }

    async hangup() {
        if (!this.simpleUser) return;
        
        try {
            this.stopRingtone();
            await this.simpleUser.hangup();
        } catch (error) {
            console.error('WebPhone: Hangup failed', error);
        }
    }

    async mute(muted = true) {
        if (!this.simpleUser) return;
        
        try {
            if (muted) {
                this.simpleUser.mute();
            } else {
                this.simpleUser.unmute();
            }
        } catch (error) {
            console.error('WebPhone: Mute failed', error);
        }
    }

    async hold(held = true) {
        if (!this.simpleUser) return;
        
        try {
            if (held) {
                await this.simpleUser.hold();
            } else {
                await this.simpleUser.unhold();
            }
        } catch (error) {
            console.error('WebPhone: Hold failed', error);
        }
    }

    async transfer(target) {
        if (!this.simpleUser || !this.simpleUser.session) return false;
        
        try {
            const destination = `sip:${target}@${this.credentials.realm}`;
            // Blind transfer
            await this.simpleUser.session.refer(Web.UserAgent.makeURI(destination));
            return true;
        } catch (error) {
            console.error('WebPhone: Transfer failed', error);
            return false;
        }
    }

    async sendDTMF(digit) {
        if (!this.simpleUser || !this.simpleUser.session) return;
        
        try {
            await this.simpleUser.sendDTMF(digit);
        } catch (error) {
            console.error('WebPhone: DTMF failed', error);
        }
    }

    playRingtone() {
        if (this.ringtoneAudio) {
            this.ringtoneAudio.play().catch(e => console.warn('Ringtone play failed:', e));
        }
    }

    stopRingtone() {
        if (this.ringtoneAudio) {
            this.ringtoneAudio.pause();
            this.ringtoneAudio.currentTime = 0;
        }
    }

    startCallTimer() {
        this.callDuration = 0;
        this.callTimer = setInterval(() => {
            this.callDuration++;
            const formatted = this.formatDuration(this.callDuration);
            if (this.onStateChange) {
                this.onStateChange({ callDuration: formatted });
            }
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

    updateState(state, data = {}) {
        console.log('WebPhone state:', state, data);
        if (this.onStateChange) {
            this.onStateChange({ state, ...data });
        }
        
        // Dispatch custom event for Alpine.js
        window.dispatchEvent(new CustomEvent('webphone:statechange', {
            detail: { state, ...data }
        }));
    }

    showIncomingNotification(number, name) {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('Incoming Call', {
                body: `${name} (${number})`,
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
            console.warn('Failed to log WebPhone event:', error);
        }
    }

    async disconnect() {
        if (this.simpleUser) {
            try {
                await this.simpleUser.unregister();
                await this.simpleUser.disconnect();
            } catch (error) {
                console.error('WebPhone disconnect error:', error);
            }
        }
    }

    // Request notification permission
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
    // Only initialize if user has an extension
    const hasExtension = document.querySelector('[data-has-extension="true"]');
    if (hasExtension) {
        window.webPhone = new WebPhone();
        WebPhone.requestNotificationPermission();
    }
});

// Export for module usage
export { WebPhone };
export default WebPhone;

