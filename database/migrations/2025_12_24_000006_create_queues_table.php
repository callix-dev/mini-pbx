<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('strategy')->default('ringall'); // ringall, leastrecent, fewestcalls, random, rrmemory, linear, wrandom
            $table->integer('timeout')->default(30);
            $table->integer('retry')->default(5);
            $table->integer('wrapuptime')->default(0);
            $table->integer('maxlen')->default(0); // 0 = unlimited
            $table->integer('weight')->default(0);
            $table->boolean('joinempty')->default(false);
            $table->boolean('leavewhenempty')->default(false);
            $table->foreignId('hold_music_id')->nullable();
            $table->foreignId('soundboard_id')->nullable();
            $table->foreignId('block_filter_group_id')->nullable();
            $table->string('announce_frequency')->nullable();
            $table->string('announce_holdtime')->default('no'); // yes, no, once
            $table->string('announce_position')->default('no'); // yes, no, limit, more
            $table->string('failover_destination_type')->nullable();
            $table->unsignedBigInteger('failover_destination_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('record_calls')->default(true);
            $table->boolean('priority_queue')->default(false);
            $table->json('business_hours')->nullable();
            $table->string('out_of_hours_destination_type')->nullable();
            $table->unsignedBigInteger('out_of_hours_destination_id')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->unique('name');
        });

        // Queue members (agents)
        Schema::create('queue_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('extension_id')->constrained()->cascadeOnDelete();
            $table->integer('penalty')->default(0);
            $table->boolean('paused')->default(false);
            $table->string('pause_reason')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->boolean('is_logged_in')->default(false);
            $table->timestamp('logged_in_at')->nullable();
            $table->boolean('auto_login')->default(false);
            $table->timestamps();

            $table->unique(['queue_id', 'extension_id']);
            $table->index('is_logged_in');
        });

        // VIP callers for priority queues
        Schema::create('vip_callers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_id')->constrained()->cascadeOnDelete();
            $table->string('caller_id', 30);
            $table->string('name')->nullable();
            $table->integer('priority')->default(1);
            $table->timestamps();

            $table->unique(['queue_id', 'caller_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vip_callers');
        Schema::dropIfExists('queue_members');
        Schema::dropIfExists('queues');
    }
};

