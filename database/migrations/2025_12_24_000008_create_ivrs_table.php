<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ivrs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('timeout')->default(10);
            $table->integer('invalid_retries')->default(3);
            $table->string('invalid_destination_type')->nullable();
            $table->unsignedBigInteger('invalid_destination_id')->nullable();
            $table->string('timeout_destination_type')->nullable();
            $table->unsignedBigInteger('timeout_destination_id')->nullable();
            $table->boolean('direct_dial')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
        });

        Schema::create('ivr_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ivr_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // welcome, menu, digit_route, time_condition, play_audio, hangup
            $table->string('digit')->nullable(); // 0-9, *, #, i (invalid), t (timeout)
            $table->unsignedBigInteger('audio_file_id')->nullable();
            $table->string('destination_type')->nullable();
            $table->unsignedBigInteger('destination_id')->nullable();
            $table->json('time_conditions')->nullable();
            $table->integer('position_x')->default(0);
            $table->integer('position_y')->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['ivr_id', 'digit']);
        });

        Schema::create('ivr_node_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_node_id')->constrained('ivr_nodes')->cascadeOnDelete();
            $table->foreignId('to_node_id')->constrained('ivr_nodes')->cascadeOnDelete();
            $table->string('condition')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ivr_node_connections');
        Schema::dropIfExists('ivr_nodes');
        Schema::dropIfExists('ivrs');
    }
};







