<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add missing PJSIP columns required by Asterisk
 * 
 * These columns may be expected by different Asterisk versions.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add missing columns to ps_contacts
        Schema::table('ps_contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('ps_contacts', 'qualify_2xx_only')) {
                $table->enum('qualify_2xx_only', ['yes', 'no'])->default('no')->after('qualify_timeout');
            }
            if (!Schema::hasColumn('ps_contacts', 'status')) {
                $table->string('status', 40)->nullable()->after('endpoint');
            }
            if (!Schema::hasColumn('ps_contacts', 'round_trip_usec')) {
                $table->string('round_trip_usec', 40)->nullable()->after('status');
            }
        });

        // Add any missing columns to ps_aors that some Asterisk versions expect
        Schema::table('ps_aors', function (Blueprint $table) {
            if (!Schema::hasColumn('ps_aors', 'qualify_2xx_only')) {
                $table->enum('qualify_2xx_only', ['yes', 'no'])->default('no')->after('qualify_timeout');
            }
            if (!Schema::hasColumn('ps_aors', 'contact')) {
                $table->string('contact', 255)->nullable()->after('id');
            }
        });

        // Add any missing columns to ps_endpoints
        Schema::table('ps_endpoints', function (Blueprint $table) {
            if (!Schema::hasColumn('ps_endpoints', 'timers')) {
                $table->enum('timers', ['yes', 'no', 'required', 'always'])->default('yes')->after('language');
            }
            if (!Schema::hasColumn('ps_endpoints', 'timers_min_se')) {
                $table->integer('timers_min_se')->default(90)->after('timers');
            }
            if (!Schema::hasColumn('ps_endpoints', 'timers_sess_expires')) {
                $table->integer('timers_sess_expires')->default(1800)->after('timers_min_se');
            }
            if (!Schema::hasColumn('ps_endpoints', '100rel')) {
                $table->enum('100rel', ['yes', 'no', 'required'])->default('yes')->after('timers_sess_expires');
            }
            if (!Schema::hasColumn('ps_endpoints', 'aggregate_mwi')) {
                $table->enum('aggregate_mwi', ['yes', 'no'])->default('yes')->after('100rel');
            }
            if (!Schema::hasColumn('ps_endpoints', 'max_audio_streams')) {
                $table->integer('max_audio_streams')->default(1)->after('aggregate_mwi');
            }
            if (!Schema::hasColumn('ps_endpoints', 'max_video_streams')) {
                $table->integer('max_video_streams')->default(1)->after('max_audio_streams');
            }
            if (!Schema::hasColumn('ps_endpoints', 'bundle')) {
                $table->enum('bundle', ['yes', 'no'])->default('no')->after('max_video_streams');
            }
            if (!Schema::hasColumn('ps_endpoints', 'rtcp_mux')) {
                $table->enum('rtcp_mux', ['yes', 'no'])->default('no')->after('bundle');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ps_contacts', function (Blueprint $table) {
            $table->dropColumn(['qualify_2xx_only', 'status', 'round_trip_usec']);
        });

        Schema::table('ps_aors', function (Blueprint $table) {
            $table->dropColumn(['qualify_2xx_only', 'contact']);
        });

        Schema::table('ps_endpoints', function (Blueprint $table) {
            $table->dropColumn([
                'timers', 'timers_min_se', 'timers_sess_expires', '100rel',
                'aggregate_mwi', 'max_audio_streams', 'max_video_streams',
                'bundle', 'rtcp_mux'
            ]);
        });
    }
};


