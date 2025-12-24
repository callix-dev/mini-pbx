import './bootstrap';
import Alpine from 'alpinejs';

// Initialize Alpine.js
window.Alpine = Alpine;
Alpine.start();

// Import Echo listeners for real-time updates
import './echo-listeners';

// Import softphone module
import './softphone';
