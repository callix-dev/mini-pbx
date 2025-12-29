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
        Schema::create('carrier_templates', function (Blueprint $table) {
            $table->id();
            
            // Provider identification
            $table->string('provider_slug'); // twilio, telnyx, vonage, ringcentral, generic_registration, generic_ip
            $table->string('provider_name'); // Display name
            $table->string('logo_path')->nullable(); // Path to provider logo
            
            // Direction
            $table->string('direction'); // inbound, outbound
            
            // Default configuration values
            $table->json('default_config'); // Pre-filled form values
            
            // Regional endpoints
            $table->json('regions')->nullable(); // {"us1": {"host": "...", "label": "US East"}}
            
            // Auth type options (for providers with multiple auth methods)
            $table->json('auth_types')->nullable(); // ["credentials", "ip"]
            
            // Required and optional fields
            $table->json('required_fields'); // Fields that must be filled
            $table->json('provider_fields')->nullable(); // Provider-specific extra fields
            
            // Help and documentation
            $table->json('help_links')->nullable(); // {"credentials": "https://...", "setup": "https://..."}
            $table->text('description')->nullable();
            
            // Display settings
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Unique constraint for provider + direction
            $table->unique(['provider_slug', 'direction']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carrier_templates');
    }
};

