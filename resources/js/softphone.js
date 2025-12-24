/**
 * WebRTC Softphone using SIP.js
 * 
 * This module handles WebRTC-based VoIP communication
 * with Asterisk via WebSocket.
 */

class Softphone {
    constructor(options = {}) {
        this.config = {
            wsServer: options.wsServer || 'wss://asterisk.example.com:8089/ws',
            extension: options.extension || '',
            password: options.password || '',
            realm: options.realm || 'asterisk',
            stunServer: options.stunServer || 'stun:stun.l.google.com:19302',
            turnServer: options.turnServer || null,
            turnUsername: options.turnUsername || null,
            turnCredential: options.turnCredential || null,
        };

        this.session = null;
        this.userAgent = null;
        this.registerer = null;
        this.state = 'idle'; // idle, connecting, registered, calling, ringing, connected
        this.callDuration = 0;
        this.callTimer = null;
        this.localStream = null;
        this.remoteStream = null;
        
        this.callbacks = {
            onStateChange: () => {},
            onIncomingCall: () => {},
            onCallConnected: () => {},
            onCallEnded: () => {},
            onError: () => {},
        };
    }

    /**
     * Initialize the softphone
     */
    async init() {
        try {
            // Check for WebRTC support
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('WebRTC is not supported in this browser');
            }

            // Request microphone permission
            this.localStream = await navigator.mediaDevices.getUserMedia({ audio: true });

            this.setState('connecting');

            // Create SIP.js User Agent (simplified for demonstration)
            // In production, you would use the actual SIP.js library
            this.setupUserAgent();

            return true;
        } catch (error) {
            this.callbacks.onError(error.message);
            console.error('Softphone initialization failed:', error);
            return false;
        }
    }

    /**
     * Setup the SIP User Agent
     */
    setupUserAgent() {
        // This is a simplified implementation
        // In production, you would use SIP.js like this:
        
        /*
        const { UserAgent, Registerer, Inviter, SessionState } = SIP;

        const uri = UserAgent.makeURI(`sip:${this.config.extension}@${this.config.realm}`);
        
        const transportOptions = {
            server: this.config.wsServer,
        };

        const userAgentOptions = {
            authorizationPassword: this.config.password,
            authorizationUsername: this.config.extension,
            transportOptions,
            uri,
            sessionDescriptionHandlerFactoryOptions: {
                iceGatheringTimeout: 500,
                peerConnectionConfiguration: {
                    iceServers: this.getIceServers(),
                },
            },
        };

        this.userAgent = new UserAgent(userAgentOptions);

        this.userAgent.delegate = {
            onInvite: (invitation) => this.handleIncomingCall(invitation),
        };

        this.registerer = new Registerer(this.userAgent);
        
        await this.userAgent.start();
        await this.registerer.register();
        */

        // For now, simulate successful connection
        setTimeout(() => {
            this.setState('registered');
        }, 1000);
    }

    /**
     * Get ICE servers configuration
     */
    getIceServers() {
        const servers = [];

        if (this.config.stunServer) {
            servers.push({ urls: this.config.stunServer });
        }

        if (this.config.turnServer) {
            servers.push({
                urls: this.config.turnServer,
                username: this.config.turnUsername,
                credential: this.config.turnCredential,
            });
        }

        return servers;
    }

    /**
     * Make an outbound call
     */
    async call(destination) {
        if (this.state !== 'registered') {
            throw new Error('Not registered');
        }

        try {
            this.setState('calling');

            // In production with SIP.js:
            /*
            const target = UserAgent.makeURI(`sip:${destination}@${this.config.realm}`);
            const inviter = new Inviter(this.userAgent, target);

            inviter.stateChange.addListener((state) => {
                switch (state) {
                    case SessionState.Establishing:
                        this.setState('calling');
                        break;
                    case SessionState.Established:
                        this.handleCallConnected(inviter);
                        break;
                    case SessionState.Terminated:
                        this.handleCallEnded();
                        break;
                }
            });

            await inviter.invite();
            this.session = inviter;
            */

            // Simulate call connection for demo
            setTimeout(() => {
                this.handleCallConnected({ remoteIdentity: { displayName: destination } });
            }, 2000);

            return true;
        } catch (error) {
            this.callbacks.onError(error.message);
            this.setState('registered');
            return false;
        }
    }

    /**
     * Answer incoming call
     */
    async answer() {
        if (!this.session || this.state !== 'ringing') {
            throw new Error('No incoming call to answer');
        }

        try {
            // In production: await this.session.accept();
            this.handleCallConnected(this.session);
            return true;
        } catch (error) {
            this.callbacks.onError(error.message);
            return false;
        }
    }

    /**
     * Reject incoming call
     */
    async reject() {
        if (!this.session || this.state !== 'ringing') {
            throw new Error('No incoming call to reject');
        }

        try {
            // In production: await this.session.reject();
            this.handleCallEnded();
            return true;
        } catch (error) {
            this.callbacks.onError(error.message);
            return false;
        }
    }

    /**
     * Hang up current call
     */
    async hangup() {
        if (!this.session) {
            return false;
        }

        try {
            // In production: await this.session.bye();
            this.handleCallEnded();
            return true;
        } catch (error) {
            this.callbacks.onError(error.message);
            return false;
        }
    }

    /**
     * Toggle mute
     */
    toggleMute() {
        if (this.localStream) {
            const audioTrack = this.localStream.getAudioTracks()[0];
            if (audioTrack) {
                audioTrack.enabled = !audioTrack.enabled;
                return !audioTrack.enabled; // Return muted state
            }
        }
        return false;
    }

    /**
     * Toggle hold
     */
    async toggleHold() {
        if (!this.session || this.state !== 'connected') {
            return false;
        }

        // In production, you would use session.hold() or session.unhold()
        return true;
    }

    /**
     * Transfer call
     */
    async transfer(destination) {
        if (!this.session || this.state !== 'connected') {
            throw new Error('No active call to transfer');
        }

        // In production: await this.session.refer(target);
        return true;
    }

    /**
     * Send DTMF tone
     */
    sendDTMF(digit) {
        if (!this.session || this.state !== 'connected') {
            return false;
        }

        // In production: this.session.sessionDescriptionHandler.sendDtmf(digit);
        console.log('DTMF:', digit);
        return true;
    }

    /**
     * Handle incoming call
     */
    handleIncomingCall(invitation) {
        this.session = invitation;
        this.setState('ringing');

        const callerInfo = {
            number: invitation.remoteIdentity?.uri?.user || 'Unknown',
            name: invitation.remoteIdentity?.displayName || '',
        };

        this.callbacks.onIncomingCall(callerInfo);
    }

    /**
     * Handle call connected
     */
    handleCallConnected(session) {
        this.session = session;
        this.setState('connected');
        this.startCallTimer();
        this.callbacks.onCallConnected({
            remoteNumber: session.remoteIdentity?.displayName || 'Unknown',
        });
    }

    /**
     * Handle call ended
     */
    handleCallEnded() {
        this.stopCallTimer();
        this.session = null;
        this.setState('registered');
        this.callbacks.onCallEnded({
            duration: this.callDuration,
        });
        this.callDuration = 0;
    }

    /**
     * Start call duration timer
     */
    startCallTimer() {
        this.callDuration = 0;
        this.callTimer = setInterval(() => {
            this.callDuration++;
        }, 1000);
    }

    /**
     * Stop call duration timer
     */
    stopCallTimer() {
        if (this.callTimer) {
            clearInterval(this.callTimer);
            this.callTimer = null;
        }
    }

    /**
     * Format call duration
     */
    formatDuration() {
        const minutes = Math.floor(this.callDuration / 60);
        const seconds = this.callDuration % 60;
        return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }

    /**
     * Set softphone state
     */
    setState(state) {
        this.state = state;
        this.callbacks.onStateChange(state);
    }

    /**
     * Register callback
     */
    on(event, callback) {
        if (this.callbacks.hasOwnProperty(event)) {
            this.callbacks[event] = callback;
        }
    }

    /**
     * Cleanup and disconnect
     */
    async disconnect() {
        if (this.session) {
            await this.hangup();
        }

        if (this.registerer) {
            // await this.registerer.unregister();
        }

        if (this.userAgent) {
            // await this.userAgent.stop();
        }

        if (this.localStream) {
            this.localStream.getTracks().forEach(track => track.stop());
        }

        this.setState('idle');
    }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Softphone;
}

// Make available globally
window.Softphone = Softphone;

