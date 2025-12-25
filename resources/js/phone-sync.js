/**
 * Phone Sync - Cross-window communication for Softphone status
 * 
 * Uses BroadcastChannel API to sync phone state between
 * the popup softphone and the main application window.
 */

class PhoneSync {
    constructor() {
        this.channel = null;
        this.state = {
            isRegistered: false,
            callState: 'idle', // idle, ringing, calling, connected
            callerNumber: '',
            callerName: '',
            callDuration: '00:00',
            callDirection: '', // inbound, outbound
            isMuted: false,
            isOnHold: false,
        };
        this.listeners = [];
        
        this.init();
    }

    init() {
        // Check if BroadcastChannel is supported
        if ('BroadcastChannel' in window) {
            this.channel = new BroadcastChannel('mini-pbx-phone');
            
            // Listen for messages from other windows
            this.channel.onmessage = (event) => {
                this.handleMessage(event.data);
            };
        } else {
            // Fallback to localStorage events
            window.addEventListener('storage', (event) => {
                if (event.key === 'mini-pbx-phone-state') {
                    const data = JSON.parse(event.newValue);
                    this.handleMessage(data);
                }
            });
        }
    }

    /**
     * Broadcast state update (called from softphone popup)
     */
    broadcast(stateUpdate) {
        this.state = { ...this.state, ...stateUpdate };
        
        const message = {
            type: 'state_update',
            state: this.state,
            timestamp: Date.now(),
        };

        if (this.channel) {
            this.channel.postMessage(message);
        }
        
        // Also use localStorage for backup/fallback
        localStorage.setItem('mini-pbx-phone-state', JSON.stringify(message));
        
        // Notify local listeners
        this.notifyListeners(this.state);
    }

    /**
     * Handle incoming message from other window
     */
    handleMessage(data) {
        if (data.type === 'state_update') {
            this.state = data.state;
            this.notifyListeners(this.state);
        } else if (data.type === 'request_state') {
            // Another window is requesting current state
            this.broadcast(this.state);
        }
    }

    /**
     * Request current state from softphone (called from main app)
     */
    requestState() {
        const message = { type: 'request_state' };
        
        if (this.channel) {
            this.channel.postMessage(message);
        }
        
        // Also check localStorage for last known state
        const stored = localStorage.getItem('mini-pbx-phone-state');
        if (stored) {
            const data = JSON.parse(stored);
            // Only use if recent (within last 30 seconds)
            if (Date.now() - data.timestamp < 30000) {
                this.state = data.state;
                this.notifyListeners(this.state);
            }
        }
    }

    /**
     * Subscribe to state changes
     */
    subscribe(callback) {
        this.listeners.push(callback);
        
        // Immediately call with current state
        callback(this.state);
        
        // Return unsubscribe function
        return () => {
            this.listeners = this.listeners.filter(l => l !== callback);
        };
    }

    /**
     * Notify all listeners of state change
     */
    notifyListeners(state) {
        this.listeners.forEach(callback => callback(state));
    }

    /**
     * Get current state
     */
    getState() {
        return this.state;
    }

    /**
     * Check if phone is in a call
     */
    isInCall() {
        return ['ringing', 'calling', 'connected'].includes(this.state.callState);
    }

    /**
     * Initiate a call to a phone number
     * Opens the softphone popup with the number to dial
     */
    initiateCall(phoneNumber) {
        const message = {
            type: 'initiate_call',
            phoneNumber: phoneNumber,
            timestamp: Date.now(),
        };

        if (this.channel) {
            this.channel.postMessage(message);
        }
        
        // Also use localStorage for backup
        localStorage.setItem('mini-pbx-phone-dial', JSON.stringify(message));
        
        // Open or focus the softphone popup
        const popupUrl = '/softphone?dial=' + encodeURIComponent(phoneNumber);
        const popup = window.open(popupUrl, 'softphone', 'width=400,height=600,resizable=yes,scrollbars=yes');
        
        if (popup) {
            popup.focus();
        }
    }

    /**
     * Broadcast logout event to close phone popup
     */
    broadcastLogout() {
        const message = {
            type: 'logout',
            timestamp: Date.now(),
        };

        if (this.channel) {
            this.channel.postMessage(message);
        }
        
        // Also use localStorage for backup
        localStorage.setItem('mini-pbx-phone-logout', Date.now().toString());
        
        // Clear heartbeat
        localStorage.removeItem('mini-pbx-phone-heartbeat');
        localStorage.removeItem('mini-pbx-phone-state');
    }

    /**
     * Subscribe to logout events (called from popup)
     */
    onLogout(callback) {
        // Listen via BroadcastChannel
        if (this.channel) {
            const originalHandler = this.channel.onmessage;
            this.channel.onmessage = (event) => {
                if (event.data.type === 'logout') {
                    callback();
                } else if (originalHandler) {
                    originalHandler(event);
                }
            };
        }
        
        // Also listen via localStorage
        window.addEventListener('storage', (event) => {
            if (event.key === 'mini-pbx-phone-logout') {
                callback();
            }
        });
    }

    /**
     * Destroy the channel
     */
    destroy() {
        if (this.channel) {
            this.channel.close();
        }
    }
}

// Hook into logout forms/buttons
document.addEventListener('DOMContentLoaded', () => {
    // Find logout forms and buttons
    const logoutForms = document.querySelectorAll('form[action*="logout"]');
    const logoutButtons = document.querySelectorAll('button[onclick*="logout"], a[href*="logout"]');
    
    logoutForms.forEach(form => {
        form.addEventListener('submit', () => {
            if (window.phoneSync) {
                window.phoneSync.broadcastLogout();
            }
        });
    });
    
    logoutButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (window.phoneSync) {
                window.phoneSync.broadcastLogout();
            }
        });
    });
});

// Create global instance
window.phoneSync = new PhoneSync();

// Export for module usage
export { PhoneSync };
export default PhoneSync;

