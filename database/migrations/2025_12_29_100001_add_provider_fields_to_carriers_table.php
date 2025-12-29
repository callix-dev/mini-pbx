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
        Schema::table('carriers', function (Blueprint $table) {
            // Provider identification
            $table->string('provider_slug')->nullable()->after('name');
            
            // Provider-specific configuration (API keys, SIDs, connection IDs, etc.)
            $table->json('provider_config')->nullable()->after('settings');
            
            // Failover carrier
            $table->foreignId('backup_carrier_id')
                ->nullable()
                ->after('priority')
                ->constrained('carriers')
                ->nullOnDelete();
            
            // Index for provider lookup
            $table->index('provider_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carriers', function (Blueprint $table) {
            $table->dropForeign(['backup_carrier_id']);
            $table->dropIndex(['provider_slug']);
            $table->dropColumn(['provider_slug', 'provider_config', 'backup_carrier_id']);
        });
    }
};

