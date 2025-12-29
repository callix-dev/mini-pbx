<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * These tables are read by Asterisk via ODBC realtime for queue configuration.
     * Extension Groups are synced to these tables automatically.
     */
    public function up(): void
    {
        // Main queue configuration table - read by Asterisk res_config_odbc
        Schema::create('asterisk_queues', function (Blueprint $table) {
            $table->string('name', 128)->primary(); // extgroup_1, extgroup_2, etc.
            $table->string('musicclass', 128)->default('default');
            $table->string('announce', 128)->nullable();
            $table->string('context', 128)->default('from-internal');
            $table->integer('timeout')->default(30); // Ring time per member
            $table->string('ringinuse', 5)->default('yes'); // Ring members already in use
            $table->integer('setinterfacevar')->default(1);
            $table->integer('setqueuevar')->default(1);
            $table->integer('setqueueentryvar')->default(1);
            $table->string('monitor_format', 8)->nullable(); // wav, gsm, etc.
            $table->string('membermacro', 128)->nullable();
            $table->string('membergosub', 128)->nullable();
            $table->string('queue_youarenext', 128)->nullable();
            $table->string('queue_thereare', 128)->nullable();
            $table->string('queue_callswaiting', 128)->nullable();
            $table->string('queue_holdtime', 128)->nullable();
            $table->string('queue_minutes', 128)->nullable();
            $table->string('queue_seconds', 128)->nullable();
            $table->string('queue_thankyou', 128)->nullable();
            $table->string('queue_lessthan', 128)->nullable();
            $table->string('queue_reporthold', 128)->nullable();
            $table->integer('announce_frequency')->default(0);
            $table->string('announce_to_first_user', 5)->default('no');
            $table->integer('min_announce_frequency')->default(15);
            $table->integer('announce_round_seconds')->default(0);
            $table->string('announce_holdtime', 128)->nullable();
            $table->integer('announce_position')->default(0);
            $table->string('announce_position_limit', 5)->default('no');
            $table->integer('periodic_announce_frequency')->default(0);
            $table->string('periodic_announce', 128)->nullable();
            $table->string('relative_periodic_announce', 5)->default('yes');
            $table->string('random_periodic_announce', 5)->default('no');
            $table->integer('retry')->default(5);
            $table->integer('wrapuptime')->default(0);
            $table->integer('penaltymemberslimit')->default(0);
            $table->string('autofill', 5)->default('yes');
            $table->string('monitor_type', 128)->nullable();
            $table->string('autopause', 5)->default('no');
            $table->integer('autopausedelay')->default(0);
            $table->string('autopausebusy', 5)->default('no');
            $table->string('autopauseunavail', 5)->default('no');
            $table->integer('maxlen')->default(0); // 0 = unlimited
            $table->integer('servicelevel')->default(60);
            $table->string('strategy', 32)->default('ringall'); // ringall, linear, leastrecent, fewestcalls, random, rrmemory
            $table->string('joinempty', 32)->default('yes');
            $table->string('leavewhenempty', 32)->default('no');
            $table->string('reportholdtime', 5)->default('no');
            $table->integer('memberdelay')->default(0);
            $table->integer('weight')->default(0);
            $table->string('timeoutrestart', 5)->default('no');
            $table->string('defaultrule', 128)->nullable();
            $table->string('timeoutpriority', 10)->default('app');
            
            // Link back to Laravel extension_group
            $table->unsignedBigInteger('extension_group_id')->nullable();
            $table->timestamps();
            
            $table->index('extension_group_id');
        });

        // Queue members table - read by Asterisk res_config_odbc
        Schema::create('asterisk_queue_members', function (Blueprint $table) {
            $table->id();
            $table->string('queue_name', 128);
            $table->string('interface', 128); // PJSIP/1001
            $table->string('membername', 128)->nullable();
            $table->string('state_interface', 128)->nullable();
            $table->integer('penalty')->default(0);
            $table->integer('paused')->default(0);
            $table->string('uniqueid', 128)->nullable();
            $table->integer('wrapuptime')->nullable();
            $table->integer('ringinuse')->nullable();
            
            $table->unique(['queue_name', 'interface']);
            $table->index('queue_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asterisk_queue_members');
        Schema::dropIfExists('asterisk_queues');
    }
};



