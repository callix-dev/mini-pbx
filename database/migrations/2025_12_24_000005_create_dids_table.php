<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dids', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('carrier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('destination_type')->nullable(); // extension, queue, ring_tree, ivr, voicemail, external
            $table->unsignedBigInteger('destination_id')->nullable();
            $table->string('after_hours_destination_type')->nullable();
            $table->unsignedBigInteger('after_hours_destination_id')->nullable();
            $table->foreignId('block_filter_group_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('time_based_routing')->default(false);
            $table->json('business_hours')->nullable();
            $table->json('caller_id_routing')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index(['destination_type', 'destination_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dids');
    }
};

