<?php

/**
 * WebPhone Configuration
 * 
 * Settings for the browser-based SIP phone (SIP.js)
 */

return [

    /*
    |--------------------------------------------------------------------------
    | WebSocket Server
    |--------------------------------------------------------------------------
    |
    | The WSS server URL for SIP.js to connect to Asterisk.
    | When proxying through Nginx, this is typically the same domain with /ws path.
    |
    */

    'wss_server' => env('WEBPHONE_WSS_SERVER'),

    /*
    |--------------------------------------------------------------------------
    | SIP Realm
    |--------------------------------------------------------------------------
    |
    | The SIP realm/domain for authentication.
    | Usually matches the Asterisk server hostname.
    |
    */

    'realm' => env('WEBPHONE_REALM', env('APP_URL') ? parse_url(env('APP_URL'), PHP_URL_HOST) : 'localhost'),

    /*
    |--------------------------------------------------------------------------
    | ICE Servers
    |--------------------------------------------------------------------------
    |
    | STUN/TURN servers for WebRTC NAT traversal.
    | Free STUN servers work for most cases, but you may need TURN for restrictive firewalls.
    |
    */

    'ice_servers' => [
        [
            'urls' => env('WEBPHONE_STUN_SERVER', 'stun:stun.l.google.com:19302'),
        ],
        // Add TURN server if needed for NAT traversal
        // [
        //     'urls' => 'turn:your-turn-server.com:3478',
        //     'username' => 'user',
        //     'credential' => 'password',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audio Settings
    |--------------------------------------------------------------------------
    |
    | Default audio settings for the WebPhone.
    |
    */

    'audio' => [
        // Echo cancellation
        'echo_cancellation' => true,
        
        // Noise suppression
        'noise_suppression' => true,
        
        // Automatic gain control
        'auto_gain_control' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeouts
    |--------------------------------------------------------------------------
    |
    | Various timeout settings in seconds.
    |
    */

    'timeouts' => [
        // How long to wait for registration
        'registration' => (int) env('WEBPHONE_REGISTRATION_TIMEOUT', 30),
        
        // How long to ring before timing out
        'ringing' => (int) env('WEBPHONE_RINGING_TIMEOUT', 60),
        
        // Reconnect delay after connection loss
        'reconnect_delay' => (int) env('WEBPHONE_RECONNECT_DELAY', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Settings
    |--------------------------------------------------------------------------
    |
    | Default UI behavior settings.
    |
    */

    'ui' => [
        // Show phone in collapsed state by default
        'collapsed_by_default' => true,
        
        // Position: 'bottom-right', 'bottom-left'
        'position' => 'bottom-right',
        
        // Play ringtone for incoming calls
        'play_ringtone' => true,
        
        // Show desktop notifications
        'desktop_notifications' => true,
    ],

];





