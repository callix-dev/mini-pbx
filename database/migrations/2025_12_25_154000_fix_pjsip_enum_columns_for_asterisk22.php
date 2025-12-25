<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Fix PJSIP columns for Asterisk 22 compatibility
 * 
 * Asterisk 22 sends 'true'/'false' instead of 'yes'/'no' for some columns.
 * Change ENUMs to VARCHAR to accept any value Asterisk sends.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For PostgreSQL, we need to drop and recreate columns
        // because ALTER COLUMN TYPE doesn't work well with enums
        
        // Fix ps_contacts columns
        Schema::table('ps_contacts', function (Blueprint $table) {
            // Drop the enum columns and recreate as varchar
        });

        // Use raw SQL for PostgreSQL compatibility
        DB::statement('ALTER TABLE ps_contacts ALTER COLUMN qualify_2xx_only TYPE VARCHAR(10)');
        DB::statement('ALTER TABLE ps_contacts ALTER COLUMN qualify_2xx_only SET DEFAULT \'no\'');
        
        DB::statement('ALTER TABLE ps_contacts ALTER COLUMN authenticate_qualify TYPE VARCHAR(10)');
        DB::statement('ALTER TABLE ps_contacts ALTER COLUMN authenticate_qualify SET DEFAULT \'no\'');
        
        DB::statement('ALTER TABLE ps_contacts ALTER COLUMN prune_on_boot TYPE VARCHAR(10)');
        DB::statement('ALTER TABLE ps_contacts ALTER COLUMN prune_on_boot SET DEFAULT \'no\'');

        // Fix ps_aors columns
        DB::statement('ALTER TABLE ps_aors ALTER COLUMN qualify_2xx_only TYPE VARCHAR(10)');
        DB::statement('ALTER TABLE ps_aors ALTER COLUMN qualify_2xx_only SET DEFAULT \'no\'');
        
        DB::statement('ALTER TABLE ps_aors ALTER COLUMN remove_existing TYPE VARCHAR(10)');
        DB::statement('ALTER TABLE ps_aors ALTER COLUMN remove_existing SET DEFAULT \'yes\'');
        
        DB::statement('ALTER TABLE ps_aors ALTER COLUMN authenticate_qualify TYPE VARCHAR(10)');
        DB::statement('ALTER TABLE ps_aors ALTER COLUMN authenticate_qualify SET DEFAULT \'no\'');

        // Fix ps_aors qualify_frequency (was enum, should be integer)
        DB::statement('ALTER TABLE ps_aors ALTER COLUMN qualify_frequency DROP DEFAULT');
        DB::statement('ALTER TABLE ps_aors ALTER COLUMN qualify_frequency TYPE INTEGER USING qualify_frequency::integer');
        DB::statement('ALTER TABLE ps_aors ALTER COLUMN qualify_frequency SET DEFAULT 60');

        // Fix ps_endpoints - convert all enum columns to varchar
        $endpointEnumColumns = [
            'direct_media', 'force_rport', 'rewrite_contact', 'rtp_symmetric',
            'ice_support', 'media_encryption_optimistic', 'media_use_received_transport',
            'use_avpf', 'trust_id_inbound', 'trust_id_outbound', 'send_pai', 'send_rpid',
            'send_diversion', 't38_udptl', 't38_udptl_nat', 'fax_detect', 'allow_transfer',
            'allow_subscribe', 'webrtc', 'one_touch_recording', 'inband_progress',
            'refer_blind_progress', 'moh_suggest', 'aggregate_mwi', 'bundle', 'rtcp_mux'
        ];

        foreach ($endpointEnumColumns as $column) {
            if (Schema::hasColumn('ps_endpoints', $column)) {
                DB::statement("ALTER TABLE ps_endpoints ALTER COLUMN {$column} TYPE VARCHAR(10)");
            }
        }

        // Fix larger enum columns
        DB::statement('ALTER TABLE ps_endpoints ALTER COLUMN dtmf_mode TYPE VARCHAR(20)');
        DB::statement('ALTER TABLE ps_endpoints ALTER COLUMN media_encryption TYPE VARCHAR(20)');
        DB::statement('ALTER TABLE ps_endpoints ALTER COLUMN t38_udptl_ec TYPE VARCHAR(20)');
        
        if (Schema::hasColumn('ps_endpoints', 'timers')) {
            DB::statement('ALTER TABLE ps_endpoints ALTER COLUMN timers TYPE VARCHAR(20)');
        }
        if (Schema::hasColumn('ps_endpoints', '100rel')) {
            DB::statement('ALTER TABLE ps_endpoints ALTER COLUMN "100rel" TYPE VARCHAR(20)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Converting back to enums is complex and rarely needed
        // Just leave as varchar
    }
};

