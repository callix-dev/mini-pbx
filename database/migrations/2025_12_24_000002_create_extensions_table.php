<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extensions', function (Blueprint $table) {
            $table->id();
            $table->string('extension', 20)->unique();
            $table->string('name');
            $table->string('password');
            $table->string('context')->default('from-internal');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->string('status')->default('offline'); // offline, online, ringing, on_call
            $table->boolean('voicemail_enabled')->default(true);
            $table->string('voicemail_password')->nullable();
            $table->string('voicemail_email')->nullable();
            $table->string('caller_id_name')->nullable();
            $table->string('caller_id_number')->nullable();
            $table->json('settings')->nullable();
            $table->timestamp('last_registered_at')->nullable();
            $table->string('last_registered_ip')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extensions');
    }
};


