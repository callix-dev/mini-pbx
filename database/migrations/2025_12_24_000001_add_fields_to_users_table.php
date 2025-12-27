<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('agent_status')->default('offline')->after('password');
            $table->foreignId('extension_id')->nullable()->after('agent_status');
            $table->string('phone')->nullable()->after('extension_id');
            $table->boolean('is_active')->default(true)->after('phone');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
            $table->string('session_id')->nullable()->after('last_login_ip');
            $table->json('notification_preferences')->nullable()->after('session_id');
            $table->string('timezone')->default('UTC')->after('notification_preferences');

            $table->index('agent_status');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'agent_status',
                'extension_id',
                'phone',
                'is_active',
                'last_login_at',
                'last_login_ip',
                'session_id',
                'notification_preferences',
                'timezone',
            ]);
        });
    }
};



