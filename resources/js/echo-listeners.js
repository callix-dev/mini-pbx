/**
 * Laravel Echo Listeners for Real-time Dashboard Updates
 * 
 * This module sets up Echo channel subscriptions for:
 * - Extension status changes
 * - Agent status changes  
 * - Call events
 * - Queue updates
 */

// Initialize dashboard listeners when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.Echo === 'undefined') {
        console.warn('Laravel Echo not initialized. Real-time updates disabled.');
        return;
    }

    initializeExtensionListeners();
    initializeCallListeners();
    initializeQueueListeners();
    initializeDashboardPresence();
});

/**
 * Listen for extension status changes
 */
function initializeExtensionListeners() {
    // Subscribe to public extensions channel
    window.Echo.channel('extensions')
        .listen('.extension.status.changed', (e) => {
            console.log('Extension status changed:', e);
            updateExtensionStatus(e);
            showNotification(`Extension ${e.extension} is now ${e.new_status}`, e.new_status);
        });

    // Listen for agent status changes
    window.Echo.channel('agents')
        .listen('.agent.status.changed', (e) => {
            console.log('Agent status changed:', e);
            updateAgentStatus(e);
        });
}

/**
 * Listen for call events
 */
function initializeCallListeners() {
    window.Echo.channel('calls')
        .listen('.call.started', (e) => {
            console.log('Call started:', e);
            addActiveCall(e);
        })
        .listen('.call.ended', (e) => {
            console.log('Call ended:', e);
            removeActiveCall(e);
            updateCallStats(e);
        });
}

/**
 * Listen for queue updates
 */
function initializeQueueListeners() {
    window.Echo.channel('queues')
        .listen('.queue.updated', (e) => {
            console.log('Queue updated:', e);
            updateQueueStats(e);
        });
}

/**
 * Join dashboard presence channel to see who's online
 */
function initializeDashboardPresence() {
    window.Echo.join('dashboard')
        .here((users) => {
            console.log('Users on dashboard:', users);
            updateOnlineUsers(users);
        })
        .joining((user) => {
            console.log('User joined dashboard:', user);
            addOnlineUser(user);
        })
        .leaving((user) => {
            console.log('User left dashboard:', user);
            removeOnlineUser(user);
        })
        .error((error) => {
            console.error('Dashboard presence error:', error);
        });
}

/**
 * Update extension status in the UI
 */
function updateExtensionStatus(data) {
    // Update extension row if on extensions page
    const extensionRow = document.querySelector(`[data-extension-id="${data.extension_id}"]`);
    if (extensionRow) {
        const statusBadge = extensionRow.querySelector('.status-badge');
        if (statusBadge) {
            statusBadge.className = `status-badge status-${data.new_status}`;
            statusBadge.textContent = data.new_status.replace('_', ' ');
        }
        
        // Update last registered time
        const lastRegistered = extensionRow.querySelector('.last-registered');
        if (lastRegistered && data.last_registered_at) {
            lastRegistered.textContent = formatRelativeTime(data.last_registered_at);
        }
    }

    // Update dashboard widget if exists
    const dashboardExtension = document.querySelector(`#dashboard-extension-${data.extension}`);
    if (dashboardExtension) {
        dashboardExtension.classList.remove('online', 'offline', 'on_call', 'ringing');
        dashboardExtension.classList.add(data.new_status);
    }

    // Dispatch custom event for Alpine.js components
    window.dispatchEvent(new CustomEvent('extension-status-changed', { detail: data }));
}

/**
 * Update agent status in the UI
 */
function updateAgentStatus(data) {
    // Update agent card if exists
    const agentCard = document.querySelector(`[data-agent-id="${data.agent_id}"]`);
    if (agentCard) {
        const statusIndicator = agentCard.querySelector('.status-indicator');
        if (statusIndicator) {
            statusIndicator.className = `status-indicator status-${data.new_status}`;
        }
    }

    // Dispatch custom event
    window.dispatchEvent(new CustomEvent('agent-status-changed', { detail: data }));
}

/**
 * Add active call to the UI
 */
function addActiveCall(data) {
    const activeCallsList = document.querySelector('#active-calls-list');
    if (activeCallsList) {
        const callElement = document.createElement('div');
        callElement.id = `call-${data.unique_id}`;
        callElement.className = 'active-call-item';
        callElement.innerHTML = `
            <div class="call-info">
                <span class="caller">${data.caller_id || 'Unknown'}</span>
                <span class="arrow">→</span>
                <span class="destination">${data.destination || 'Unknown'}</span>
            </div>
            <span class="call-type badge badge-${data.type}">${data.type}</span>
        `;
        activeCallsList.appendChild(callElement);
    }

    // Update active calls counter
    updateActiveCallsCount(1);

    // Dispatch custom event
    window.dispatchEvent(new CustomEvent('call-started', { detail: data }));
}

/**
 * Remove active call from the UI
 */
function removeActiveCall(data) {
    const callElement = document.querySelector(`#call-${data.unique_id}`);
    if (callElement) {
        callElement.remove();
    }

    // Update active calls counter
    updateActiveCallsCount(-1);

    // Dispatch custom event
    window.dispatchEvent(new CustomEvent('call-ended', { detail: data }));
}

/**
 * Update call statistics
 */
function updateCallStats(data) {
    // Increment call count based on status
    const counterId = `calls-${data.status}-count`;
    const counter = document.querySelector(`#${counterId}`);
    if (counter) {
        counter.textContent = parseInt(counter.textContent || 0) + 1;
    }
}

/**
 * Update queue statistics
 */
function updateQueueStats(data) {
    const queueWidget = document.querySelector(`[data-queue-name="${data.queue_name}"]`);
    if (queueWidget) {
        const waitingCount = queueWidget.querySelector('.waiting-count');
        if (waitingCount) {
            waitingCount.textContent = data.waiting;
        }
    }

    // Dispatch custom event
    window.dispatchEvent(new CustomEvent('queue-updated', { detail: data }));
}

/**
 * Update active calls count
 */
function updateActiveCallsCount(delta) {
    const counter = document.querySelector('#active-calls-count');
    if (counter) {
        const current = parseInt(counter.textContent || 0);
        counter.textContent = Math.max(0, current + delta);
    }
}

/**
 * Update online users list
 */
function updateOnlineUsers(users) {
    const onlineList = document.querySelector('#online-users-list');
    if (onlineList) {
        onlineList.innerHTML = users.map(user => `
            <div class="online-user" data-user-id="${user.id}">
                <span class="user-name">${user.name}</span>
                ${user.extension ? `<span class="user-extension">(${user.extension})</span>` : ''}
            </div>
        `).join('');
    }

    const onlineCount = document.querySelector('#online-users-count');
    if (onlineCount) {
        onlineCount.textContent = users.length;
    }
}

/**
 * Add user to online list
 */
function addOnlineUser(user) {
    const onlineList = document.querySelector('#online-users-list');
    if (onlineList) {
        const userElement = document.createElement('div');
        userElement.className = 'online-user';
        userElement.dataset.userId = user.id;
        userElement.innerHTML = `
            <span class="user-name">${user.name}</span>
            ${user.extension ? `<span class="user-extension">(${user.extension})</span>` : ''}
        `;
        onlineList.appendChild(userElement);
    }

    // Update count
    const onlineCount = document.querySelector('#online-users-count');
    if (onlineCount) {
        onlineCount.textContent = parseInt(onlineCount.textContent || 0) + 1;
    }
}

/**
 * Remove user from online list
 */
function removeOnlineUser(user) {
    const userElement = document.querySelector(`[data-user-id="${user.id}"]`);
    if (userElement) {
        userElement.remove();
    }

    // Update count
    const onlineCount = document.querySelector('#online-users-count');
    if (onlineCount) {
        const current = parseInt(onlineCount.textContent || 0);
        onlineCount.textContent = Math.max(0, current - 1);
    }
}

/**
 * Show notification toast
 */
function showNotification(message, type = 'info') {
    // Check if notifications container exists
    let container = document.querySelector('#notifications-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notifications-container';
        container.className = 'fixed top-4 right-4 z-50 space-y-2';
        document.body.appendChild(container);
    }

    const notification = document.createElement('div');
    notification.className = `notification notification-${type} bg-white dark:bg-gray-800 shadow-lg rounded-lg p-4 transform transition-all duration-300 translate-x-full`;
    notification.innerHTML = `
        <div class="flex items-center">
            <span class="notification-icon mr-2">${getStatusIcon(type)}</span>
            <span class="notification-message">${message}</span>
        </div>
    `;

    container.appendChild(notification);

    // Animate in
    requestAnimationFrame(() => {
        notification.classList.remove('translate-x-full');
    });

    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

/**
 * Get status icon
 */
function getStatusIcon(status) {
    const icons = {
        online: '🟢',
        offline: '🔴',
        on_call: '📞',
        ringing: '🔔',
        info: 'ℹ️',
        success: '✅',
        error: '❌',
    };
    return icons[status] || icons.info;
}

/**
 * Format relative time
 */
function formatRelativeTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffSecs = Math.floor(diffMs / 1000);
    const diffMins = Math.floor(diffSecs / 60);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffSecs < 60) return 'just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    return `${diffDays}d ago`;
}

// Export functions for use in other modules
window.EchoListeners = {
    updateExtensionStatus,
    updateAgentStatus,
    addActiveCall,
    removeActiveCall,
    showNotification,
};

