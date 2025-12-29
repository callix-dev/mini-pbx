<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extension_groups', function (Blueprint $table) {
            // Group dialing number (e.g., 601 = *601 or direct dial)
            $table->string('group_number', 10)->nullable()->unique()->after('name');
            
            // Pickup group number (for call pickup feature)
            $table->integer('pickup_group')->nullable()->after('group_number');
            
            // Music on hold class
            $table->string('music_on_hold')->default('default')->after('ring_time');
            
            // Announce options
            $table->boolean('announce_holdtime')->default(false)->after('music_on_hold');
            $table->boolean('announce_position')->default(false)->after('announce_holdtime');
            
            // Timeout destination (where to route if no one answers)
            $table->string('timeout_destination_type')->nullable()->after('announce_position');
            $table->unsignedBigInteger('timeout_destination_id')->nullable()->after('timeout_destination_type');
            
            // Failover destination (if all members unavailable)
            $table->string('failover_destination_type')->nullable()->after('timeout_destination_id');
            $table->unsignedBigInteger('failover_destination_id')->nullable()->after('failover_destination_type');
            
            // Recording options
            $table->boolean('record_calls')->default(false)->after('failover_destination_id');
            
            // Statistics
            $table->unsignedInteger('total_calls')->default(0)->after('record_calls');
            $table->unsignedInteger('answered_calls')->default(0)->after('total_calls');
            $table->unsignedInteger('missed_calls')->default(0)->after('answered_calls');
            $table->unsignedInteger('total_talk_time')->default(0)->after('missed_calls');
            
            // Indexes
            $table->index('group_number');
            $table->index('pickup_group');
        });

        // Add pickup_group to extensions table
        Schema::table('extensions', function (Blueprint $table) {
            $table->integer('pickup_group')->nullable()->after('voicemail_enabled');
            $table->index('pickup_group');
        });
    }

    public function down(): void
    {
        Schema::table('extension_groups', function (Blueprint $table) {
            $table->dropIndex(['group_number']);
            $table->dropIndex(['pickup_group']);
            
            $table->dropColumn([
                'group_number',
                'pickup_group',
                'music_on_hold',
                'announce_holdtime',
                'announce_position',
                'timeout_destination_type',
                'timeout_destination_id',
                'failover_destination_type',
                'failover_destination_id',
                'record_calls',
                'total_calls',
                'answered_calls',
                'missed_calls',
                'total_talk_time',
            ]);
        });

        Schema::table('extensions', function (Blueprint $table) {
            $table->dropIndex(['pickup_group']);
            $table->dropColumn('pickup_group');
        });
    }
};

