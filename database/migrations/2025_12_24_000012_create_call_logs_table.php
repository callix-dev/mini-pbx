<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('uniqueid', 100)->unique();
            $table->string('linkedid', 100)->nullable()->index();
            $table->string('type'); // inbound, outbound, internal
            $table->string('direction'); // in, out
            $table->string('caller_id', 50);
            $table->string('caller_name')->nullable();
            $table->string('callee_id', 50);
            $table->string('callee_name')->nullable();
            $table->string('did', 30)->nullable();
            $table->foreignId('extension_id')->nullable();
            $table->foreignId('queue_id')->nullable();
            $table->foreignId('carrier_id')->nullable();
            $table->string('status'); // answered, missed, busy, failed, voicemail
            $table->timestamp('start_time');
            $table->timestamp('answer_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->integer('duration')->default(0); // total duration
            $table->integer('billable_duration')->default(0); // answered duration
            $table->integer('wait_time')->default(0); // queue wait time
            $table->string('hangup_cause')->nullable();
            $table->string('hangup_by')->nullable(); // caller, callee, system
            $table->string('recording_path')->nullable();
            $table->foreignId('disposition_id')->nullable();
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('status');
            $table->index('start_time');
            $table->index(['extension_id', 'start_time']);
            $table->index(['queue_id', 'start_time']);
            $table->index('caller_id');
        });

        // Dispositions / Wrap-up codes
        Schema::create('dispositions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('color')->default('#6366f1');
            $table->boolean('requires_callback')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Call notes/tags
        Schema::create('call_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('note');
            $table->boolean('is_private')->default(false);
            $table->timestamps();

            $table->index(['call_log_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_notes');
        Schema::dropIfExists('dispositions');
        Schema::dropIfExists('call_logs');
    }
};


