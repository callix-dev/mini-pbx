<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('extension_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extension_id')->constrained()->onDelete('cascade');
            $table->string('public_ip', 45)->nullable();
            $table->string('local_ip', 45)->nullable();
            $table->integer('port')->nullable();
            $table->string('transport', 20)->nullable(); // ws, wss, udp, tcp, tls
            $table->string('user_agent')->nullable();
            $table->string('contact_uri')->nullable();
            $table->string('event_type', 20)->default('registered'); // registered, unregistered, expired
            $table->integer('expiry')->nullable(); // Registration expiry in seconds
            $table->json('metadata')->nullable(); // Additional data from AMI
            $table->timestamp('registered_at');
            $table->timestamps();

            $table->index(['extension_id', 'registered_at']);
            $table->index('public_ip');
            $table->index('event_type');
        });

        // Add public_ip column to extensions table if not exists
        if (!Schema::hasColumn('extensions', 'public_ip')) {
            Schema::table('extensions', function (Blueprint $table) {
                $table->string('public_ip', 45)->nullable()->after('last_registered_ip');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extension_registrations');

        if (Schema::hasColumn('extensions', 'public_ip')) {
            Schema::table('extensions', function (Blueprint $table) {
                $table->dropColumn('public_ip');
            });
        }
    }
};
