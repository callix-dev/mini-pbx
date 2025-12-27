<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PJSIP Realtime Tables for Asterisk
 * 
 * These tables are read directly by Asterisk's realtime engine.
 * The schema follows Asterisk's PJSIP realtime requirements.
 * 
 * @see https://wiki.asterisk.org/wiki/display/AST/PJSIP+Configuration+for+Realtime
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ps_endpoints - Main PJSIP endpoint configuration
        Schema::create('ps_endpoints', function (Blueprint $table) {
            $table->string('id', 40)->primary(); // Extension number
            $table->string('transport', 40)->nullable();
            $table->string('aors', 200)->nullable(); // Address of record reference
            $table->string('auth', 40)->nullable(); // Auth reference
            $table->string('context', 40)->default('from-internal');
            $table->string('disallow', 200)->default('all');
            $table->string('allow', 200)->default('ulaw,alaw,g722,opus');
            $table->enum('direct_media', ['yes', 'no'])->default('no');
            $table->enum('force_rport', ['yes', 'no'])->default('yes');
            $table->enum('rewrite_contact', ['yes', 'no'])->default('yes');
            $table->enum('rtp_symmetric', ['yes', 'no'])->default('yes');
            $table->string('callerid', 100)->nullable();
            $table->string('callerid_privacy', 40)->nullable();
            $table->string('callerid_tag', 40)->nullable();
            $table->string('mailboxes', 100)->nullable(); // Voicemail mailbox
            $table->string('voicemail_extension', 40)->nullable();
            $table->string('named_call_group', 40)->nullable();
            $table->string('named_pickup_group', 40)->nullable();
            $table->string('call_group', 40)->nullable();
            $table->string('pickup_group', 40)->nullable();
            $table->string('device_state_busy_at', 10)->nullable();
            $table->enum('dtmf_mode', ['rfc4733', 'inband', 'info', 'auto', 'auto_info'])->default('rfc4733');
            $table->enum('ice_support', ['yes', 'no'])->default('yes');
            $table->enum('media_encryption', ['no', 'sdes', 'dtls'])->default('no');
            $table->enum('media_encryption_optimistic', ['yes', 'no'])->default('no');
            $table->enum('media_use_received_transport', ['yes', 'no'])->default('no');
            $table->enum('use_avpf', ['yes', 'no'])->default('no');
            $table->enum('trust_id_inbound', ['yes', 'no'])->default('no');
            $table->enum('trust_id_outbound', ['yes', 'no'])->default('no');
            $table->enum('send_pai', ['yes', 'no'])->default('yes');
            $table->enum('send_rpid', ['yes', 'no'])->default('yes');
            $table->enum('send_diversion', ['yes', 'no'])->default('yes');
            $table->string('outbound_proxy', 256)->nullable();
            $table->enum('t38_udptl', ['yes', 'no'])->default('no');
            $table->enum('t38_udptl_ec', ['none', 'fec', 'redundancy'])->default('none');
            $table->integer('t38_udptl_maxdatagram')->nullable();
            $table->enum('t38_udptl_nat', ['yes', 'no'])->default('no');
            $table->enum('fax_detect', ['yes', 'no'])->default('no');
            $table->enum('allow_transfer', ['yes', 'no'])->default('yes');
            $table->enum('allow_subscribe', ['yes', 'no'])->default('yes');
            $table->string('sdp_owner', 40)->default('-');
            $table->string('sdp_session', 40)->default('Asterisk');
            $table->integer('tos_audio')->nullable();
            $table->integer('tos_video')->nullable();
            $table->integer('cos_audio')->nullable();
            $table->integer('cos_video')->nullable();
            $table->enum('webrtc', ['yes', 'no'])->default('no');
            $table->string('language', 10)->default('en');
            $table->string('accountcode', 80)->nullable();
            $table->string('from_user', 40)->nullable();
            $table->string('from_domain', 40)->nullable();
            $table->string('mwi_from_user', 40)->nullable();
            $table->string('record_on_feature', 40)->nullable();
            $table->string('record_off_feature', 40)->nullable();
            $table->enum('one_touch_recording', ['yes', 'no'])->default('no');
            $table->enum('inband_progress', ['yes', 'no'])->default('no');
            $table->enum('refer_blind_progress', ['yes', 'no'])->default('yes');
            $table->enum('moh_suggest', ['yes', 'no'])->nullable();
            $table->string('message_context', 40)->nullable();
            
            $table->index('context');
        });

        // ps_auths - Authentication credentials
        Schema::create('ps_auths', function (Blueprint $table) {
            $table->string('id', 40)->primary(); // Same as endpoint ID
            $table->enum('auth_type', ['userpass', 'md5', 'google_oauth'])->default('userpass');
            $table->string('username', 40); // SIP username
            $table->string('password', 80)->nullable(); // Plain text for userpass
            $table->string('md5_cred', 40)->nullable(); // MD5 hash for md5 auth
            $table->string('realm', 40)->nullable();
            $table->integer('nonce_lifetime')->default(32);
            
            $table->index('username');
        });

        // ps_aors - Address of Record (stores registration info)
        Schema::create('ps_aors', function (Blueprint $table) {
            $table->string('id', 40)->primary(); // Same as endpoint ID
            $table->integer('max_contacts')->default(1);
            $table->enum('remove_existing', ['yes', 'no'])->default('yes');
            $table->integer('minimum_expiration')->default(60);
            $table->integer('maximum_expiration')->default(7200);
            $table->integer('default_expiration')->default(3600);
            $table->enum('qualify_frequency', ['0', '60'])->default('60'); // Qualify every 60 seconds
            $table->float('qualify_timeout')->default(3.0);
            $table->enum('authenticate_qualify', ['yes', 'no'])->default('no');
            $table->string('outbound_proxy', 256)->nullable();
            $table->string('support_path', 10)->nullable();
            $table->string('mailboxes', 100)->nullable();
            $table->string('voicemail_extension', 40)->nullable();
        });

        // ps_contacts - Dynamically populated by Asterisk when devices register
        // This table is written to by Asterisk, not by Laravel
        Schema::create('ps_contacts', function (Blueprint $table) {
            $table->string('id', 255)->primary();
            $table->string('uri', 511)->nullable();
            $table->string('expiration_time', 40)->nullable();
            $table->float('qualify_frequency')->default(0);
            $table->string('outbound_proxy', 256)->nullable();
            $table->string('path', 512)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->float('qualify_timeout')->default(3.0);
            $table->string('reg_server', 255)->nullable();
            $table->enum('authenticate_qualify', ['yes', 'no'])->default('no');
            $table->string('via_addr', 40)->nullable();
            $table->integer('via_port')->nullable();
            $table->string('call_id', 255)->nullable();
            $table->string('endpoint', 40)->nullable();
            $table->enum('prune_on_boot', ['yes', 'no'])->default('no');
            
            $table->index('endpoint');
        });

        // ps_domain_aliases - Optional domain aliases
        Schema::create('ps_domain_aliases', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->string('domain', 80);
            
            $table->index('domain');
        });

        // ps_endpoint_id_ips - IP-based endpoint identification
        Schema::create('ps_endpoint_id_ips', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->string('endpoint', 40);
            $table->string('match', 80); // IP address or CIDR
            $table->integer('srv_lookups')->default(1);
            $table->string('match_header', 255)->nullable();
            
            $table->index('endpoint');
        });

        // ps_registrations - Outbound registrations (for trunks)
        Schema::create('ps_registrations', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->string('auth_rejection_permanent', 10)->default('yes');
            $table->string('client_uri', 255);
            $table->string('contact_user', 40)->nullable();
            $table->integer('expiration')->default(3600);
            $table->integer('max_retries')->default(10);
            $table->string('outbound_auth', 40)->nullable();
            $table->string('outbound_proxy', 256)->nullable();
            $table->integer('retry_interval')->default(60);
            $table->integer('forbidden_retry_interval')->default(0);
            $table->string('server_uri', 255);
            $table->string('transport', 40)->nullable();
            $table->enum('support_path', ['yes', 'no'])->default('no');
            $table->enum('support_outbound', ['yes', 'no'])->default('no');
            $table->string('contact_header_params', 255)->nullable();
            $table->enum('line', ['yes', 'no'])->default('no');
            $table->string('endpoint', 40)->nullable();
        });

        // ps_transports - SIP transports (usually configured in file, but can be realtime)
        Schema::create('ps_transports', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->enum('async_operations', ['0', '1'])->default('1');
            $table->string('bind', 40)->default('0.0.0.0:5060');
            $table->string('ca_list_file', 200)->nullable();
            $table->string('ca_list_path', 200)->nullable();
            $table->string('cert_file', 200)->nullable();
            $table->string('cipher', 200)->nullable();
            $table->string('domain', 40)->nullable();
            $table->string('external_media_address', 40)->nullable();
            $table->string('external_signaling_address', 40)->nullable();
            $table->integer('external_signaling_port')->nullable();
            $table->enum('method', ['default', 'unspecified', 'tlsv1', 'tlsv1_1', 'tlsv1_2', 'sslv2', 'sslv23', 'sslv3'])->default('default');
            $table->string('local_net', 40)->nullable();
            $table->string('password', 40)->nullable();
            $table->string('priv_key_file', 200)->nullable();
            $table->enum('protocol', ['udp', 'tcp', 'tls', 'ws', 'wss'])->default('udp');
            $table->enum('require_client_cert', ['yes', 'no'])->default('no');
            $table->enum('verify_client', ['yes', 'no'])->default('no');
            $table->enum('verify_server', ['yes', 'no'])->default('no');
            $table->integer('tos')->nullable();
            $table->integer('cos')->nullable();
            $table->enum('allow_reload', ['yes', 'no'])->default('yes');
            $table->enum('symmetric_transport', ['yes', 'no'])->default('no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ps_transports');
        Schema::dropIfExists('ps_registrations');
        Schema::dropIfExists('ps_endpoint_id_ips');
        Schema::dropIfExists('ps_domain_aliases');
        Schema::dropIfExists('ps_contacts');
        Schema::dropIfExists('ps_aors');
        Schema::dropIfExists('ps_auths');
        Schema::dropIfExists('ps_endpoints');
    }
};



