<?php

/**
 * Asterisk Configuration
 * 
 * Settings for Asterisk AMI, ARI, and PJSIP integration
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Asterisk Manager Interface (AMI)
    |--------------------------------------------------------------------------
    |
    | Configuration for connecting to Asterisk Manager Interface.
    | AMI is used for real-time event monitoring and command execution.
    |
    */

    'ami' => [
        'host' => env('AMI_HOST', '127.0.0.1'),
        'port' => (int) env('AMI_PORT', 5038),
        'username' => env('AMI_USERNAME', 'admin'),
        'password' => env('AMI_PASSWORD', ''),
        'connect_timeout' => (int) env('AMI_CONNECT_TIMEOUT', 10),
        'read_timeout' => (int) env('AMI_READ_TIMEOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Asterisk REST Interface (ARI)
    |--------------------------------------------------------------------------
    |
    | Configuration for connecting to Asterisk REST Interface.
    | ARI is used for programmatic call control.
    |
    */

    'ari' => [
        'host' => env('ARI_HOST', '127.0.0.1'),
        'port' => (int) env('ARI_PORT', 8088),
        'username' => env('ARI_USERNAME', 'admin'),
        'password' => env('ARI_PASSWORD', ''),
        'app_name' => env('ARI_APP_NAME', 'mini-pbx'),
        'ssl' => (bool) env('ARI_SSL', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | PJSIP Configuration
    |--------------------------------------------------------------------------
    |
    | Default settings for PJSIP endpoint configuration.
    | These are applied when syncing extensions to the PJSIP realtime tables.
    |
    */

    'pjsip' => [
        // Default transport for endpoints
        'default_transport' => env('PJSIP_DEFAULT_TRANSPORT', 'transport-udp'),
        
        // Default context for incoming calls
        'default_context' => env('PJSIP_DEFAULT_CONTEXT', 'from-internal'),
        
        // Allowed codecs (comma-separated)
        'allowed_codecs' => env('PJSIP_ALLOWED_CODECS', 'ulaw,alaw,g722,opus'),
        
        // WebRTC transport name (for browser-based softphones)
        'webrtc_transport' => env('PJSIP_WEBRTC_TRANSPORT', 'transport-wss'),
        
        // Registration expiry in seconds
        'registration_expiry' => (int) env('PJSIP_REGISTRATION_EXPIRY', 3600),
        
        // Maximum contacts per endpoint
        'max_contacts' => (int) env('PJSIP_MAX_CONTACTS', 1),
        
        // Qualify frequency (how often to check if endpoint is reachable)
        'qualify_frequency' => (int) env('PJSIP_QUALIFY_FREQUENCY', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dialplan Contexts
    |--------------------------------------------------------------------------
    |
    | Named contexts used in dialplan configuration.
    |
    */

    'contexts' => [
        // Internal calls between extensions
        'internal' => env('ASTERISK_CONTEXT_INTERNAL', 'from-internal'),
        
        // Inbound calls from carriers/trunks
        'inbound' => env('ASTERISK_CONTEXT_INBOUND', 'from-trunk'),
        
        // Outbound calls to PSTN
        'outbound' => env('ASTERISK_CONTEXT_OUTBOUND', 'to-trunk'),
        
        // Parking context
        'parking' => env('ASTERISK_CONTEXT_PARKING', 'parkedcalls'),
        
        // Queue context
        'queue' => env('ASTERISK_CONTEXT_QUEUE', 'from-queue'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Voicemail Settings
    |--------------------------------------------------------------------------
    |
    | Default voicemail configuration.
    |
    */

    'voicemail' => [
        // Default voicemail context
        'context' => env('VOICEMAIL_CONTEXT', 'default'),
        
        // Voicemail extension (dial code)
        'extension' => env('VOICEMAIL_EXTENSION', '*97'),
        
        // Max message duration in seconds
        'max_duration' => (int) env('VOICEMAIL_MAX_DURATION', 300),
        
        // Max messages per mailbox
        'max_messages' => (int) env('VOICEMAIL_MAX_MESSAGES', 100),
        
        // Email notification settings
        'email_from' => env('VOICEMAIL_EMAIL_FROM', env('MAIL_FROM_ADDRESS', 'voicemail@example.com')),
        'email_subject' => env('VOICEMAIL_EMAIL_SUBJECT', 'New Voicemail from ${VM_CIDNUM}'),
        'attach_audio' => (bool) env('VOICEMAIL_ATTACH_AUDIO', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Recording Settings
    |--------------------------------------------------------------------------
    |
    | Call recording configuration.
    |
    */

    'recordings' => [
        // Path where recordings are stored
        'path' => env('ASTERISK_RECORDINGS_PATH', '/var/spool/asterisk/monitor'),
        
        // Recording format
        'format' => env('ASTERISK_RECORDING_FORMAT', 'wav'),
        
        // Automatically record all calls
        'auto_record' => (bool) env('ASTERISK_AUTO_RECORD', false),
        
        // Mix channels (combine caller and callee into single file)
        'mix_channels' => (bool) env('ASTERISK_MIX_CHANNELS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | CDR Settings
    |--------------------------------------------------------------------------
    |
    | Call Detail Record configuration.
    |
    */

    'cdr' => [
        // Enable CDR processing
        'enabled' => (bool) env('ASTERISK_CDR_ENABLED', true),
        
        // CDR database table (if using Asterisk realtime CDR)
        'table' => env('ASTERISK_CDR_TABLE', 'cdr'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    |
    | Default queue configuration.
    |
    */

    'queues' => [
        // Default music on hold class
        'moh_class' => env('ASTERISK_QUEUE_MOH', 'default'),
        
        // Announce position in queue
        'announce_position' => (bool) env('ASTERISK_QUEUE_ANNOUNCE_POSITION', true),
        
        // Announce hold time
        'announce_holdtime' => (bool) env('ASTERISK_QUEUE_ANNOUNCE_HOLDTIME', true),
        
        // Ring strategy options: ringall, leastrecent, fewestcalls, random, rrmemory, linear, wrandom
        'default_strategy' => env('ASTERISK_QUEUE_DEFAULT_STRATEGY', 'rrmemory'),
        
        // Maximum wait time in seconds (0 = unlimited)
        'max_wait_time' => (int) env('ASTERISK_QUEUE_MAX_WAIT', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Codes
    |--------------------------------------------------------------------------
    |
    | Star codes for various features.
    |
    */

    'feature_codes' => [
        'voicemail' => '*97',
        'voicemail_direct' => '*98',
        'call_pickup' => '*8',
        'directed_pickup' => '**',
        'attended_transfer' => '*2',
        'blind_transfer' => '##',
        'call_parking' => '#72',
        'parking_lot' => '700',
        'do_not_disturb_toggle' => '*78',
        'call_forward_activate' => '*72',
        'call_forward_deactivate' => '*73',
        'record_call' => '*1',
        'spy' => '*556',
        'whisper' => '*557',
        'barge' => '*558',
        'agent_login' => '*45',
        'agent_logout' => '*46',
        'agent_pause' => '*47',
        'agent_unpause' => '*48',
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Settings for how the Laravel app integrates with Asterisk.
    |
    */

    'integration' => [
        // Sync method: 'realtime' (database), 'config' (file), 'both'
        'sync_method' => env('ASTERISK_SYNC_METHOD', 'realtime'),
        
        // Config file path (if using file sync)
        'config_path' => env('ASTERISK_CONFIG_PATH', '/etc/asterisk'),
        
        // Auto-reload Asterisk after config changes (only for file sync)
        'auto_reload' => (bool) env('ASTERISK_AUTO_RELOAD', true),
    ],

];

