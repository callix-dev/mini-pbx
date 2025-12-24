<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hold music classes
        Schema::create('hold_music', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('directory_name')->unique();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Hold music files
        Schema::create('hold_music_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hold_music_id')->constrained('hold_music')->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('converted_path')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Soundboards
        Schema::create('soundboards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Soundboard clips
        Schema::create('soundboard_clips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('soundboard_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('converted_path')->nullable();
            $table->integer('duration')->nullable();
            $table->string('shortcut_key')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // General audio files (for IVR, announcements, etc.)
        Schema::create('audio_files', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // ivr_prompt, announcement, greeting
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('converted_path')->nullable();
            $table->integer('duration')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audio_files');
        Schema::dropIfExists('soundboard_clips');
        Schema::dropIfExists('soundboards');
        Schema::dropIfExists('hold_music_files');
        Schema::dropIfExists('hold_music');
    }
};

