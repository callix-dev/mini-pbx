<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('key', 64)->unique();
            $table->string('secret_hash');
            $table->json('permissions')->nullable();
            $table->json('ip_whitelist')->nullable();
            $table->integer('rate_limit')->default(60); // requests per minute
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['key', 'is_active']);
        });

        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_key_id')->constrained()->cascadeOnDelete();
            $table->string('method', 10);
            $table->string('endpoint');
            $table->string('ip_address', 45);
            $table->integer('response_code');
            $table->integer('response_time'); // milliseconds
            $table->json('request_data')->nullable();
            $table->timestamp('created_at');

            $table->index(['api_key_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
        Schema::dropIfExists('api_keys');
    }
};

