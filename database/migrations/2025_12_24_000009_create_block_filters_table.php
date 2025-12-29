<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('block_filter_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
        });

        Schema::create('block_filters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_filter_group_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // blacklist, whitelist
            $table->string('pattern'); // phone number or pattern
            $table->string('match_type')->default('exact'); // exact, prefix, regex
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('expires_at')->nullable();
            $table->timestamps();

            $table->index(['block_filter_group_id', 'type', 'is_active']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_filters');
        Schema::dropIfExists('block_filter_groups');
    }
};







