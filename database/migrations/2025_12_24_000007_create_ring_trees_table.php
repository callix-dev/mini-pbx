<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ring_trees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
        });

        Schema::create('ring_tree_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ring_tree_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('level')->default(1); // max 3 levels
            $table->integer('position')->default(0);
            $table->string('destination_type'); // extension, extension_group, queue, hangup, voicemail, block_filter
            $table->unsignedBigInteger('destination_id')->nullable();
            $table->integer('timeout')->default(20);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('ring_tree_nodes')->cascadeOnDelete();
            $table->index(['ring_tree_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ring_tree_nodes');
        Schema::dropIfExists('ring_trees');
    }
};



