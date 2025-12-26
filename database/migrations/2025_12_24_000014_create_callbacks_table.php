<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('callbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('call_log_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone_number', 30);
            $table->string('contact_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('scheduled_at');
            $table->timestamp('reminded_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('status')->default('pending'); // pending, reminded, completed, cancelled
            $table->timestamps();

            $table->index(['user_id', 'status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('callbacks');
    }
};


