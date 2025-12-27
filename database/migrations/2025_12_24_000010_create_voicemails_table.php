<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voicemails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extension_id')->constrained()->cascadeOnDelete();
            $table->string('caller_id', 50);
            $table->string('caller_name')->nullable();
            $table->string('file_path');
            $table->integer('duration')->default(0);
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->text('transcription')->nullable();
            $table->boolean('is_forwarded')->default(false);
            $table->foreignId('forwarded_from_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['extension_id', 'is_read']);
            $table->index('created_at');
        });

        Schema::create('voicemail_greetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extension_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // unavailable, busy, temp, name
            $table->string('file_path');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['extension_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voicemail_greetings');
        Schema::dropIfExists('voicemails');
    }
};





