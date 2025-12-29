<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extension_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('ring_strategy')->default('ringall'); // ringall, hunt, memoryhunt, leastrecent, fewestcalls, random
            $table->integer('ring_time')->default(20);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
        });

        Schema::create('extension_extension_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extension_id')->constrained()->cascadeOnDelete();
            $table->foreignId('extension_group_id')->constrained()->cascadeOnDelete();
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->unique(['extension_id', 'extension_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extension_extension_group');
        Schema::dropIfExists('extension_groups');
    }
};







