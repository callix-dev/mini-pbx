<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sip_security_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('event_time')->useCurrent();
            
            // Event classification
            $table->string('event_type', 20); // INVITE, REGISTER, OPTIONS, BYE, ACK, CANCEL, etc.
            $table->string('direction', 10); // inbound, outbound
            
            // Source information
            $table->string('source_ip', 45); // IPv4 or IPv6
            $table->unsignedInteger('source_port')->nullable();
            
            // Destination information
            $table->string('destination_ip', 45)->nullable();
            $table->unsignedInteger('destination_port')->nullable();
            
            // SIP URIs
            $table->string('from_uri', 255)->nullable();
            $table->string('to_uri', 255)->nullable();
            
            // Caller/Callee identification
            $table->string('caller_id', 50)->nullable();
            $table->string('caller_name', 100)->nullable();
            $table->string('callee_id', 50)->nullable();
            
            // Carrier/Endpoint identification
            $table->foreignId('carrier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('endpoint', 100)->nullable(); // PJSIP endpoint name
            
            // Status and result
            $table->string('status', 20); // ALLOWED, REJECTED, FAILED, UNKNOWN
            $table->string('reject_reason', 255)->nullable();
            $table->unsignedSmallInteger('sip_response_code')->nullable();
            
            // SIP identifiers
            $table->string('call_id', 255)->nullable(); // SIP Call-ID header
            $table->string('uniqueid', 100)->nullable(); // Asterisk UNIQUEID
            
            // Additional data
            $table->json('metadata')->nullable(); // Additional SIP headers, context
            
            $table->timestamps();
            
            // Indexes for common queries
            $table->index('event_time');
            $table->index('event_type');
            $table->index('direction');
            $table->index('source_ip');
            $table->index('status');
            $table->index('caller_id');
            $table->index('callee_id');
            $table->index(['status', 'event_time']); // For security monitoring
            $table->index(['source_ip', 'status']); // For IP-based analysis
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sip_security_logs');
    }
};

