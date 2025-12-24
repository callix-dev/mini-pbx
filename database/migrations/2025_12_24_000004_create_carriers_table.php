<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carriers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // inbound, outbound
            $table->string('technology')->default('pjsip'); // pjsip
            $table->string('host');
            $table->integer('port')->default(5060);
            $table->string('transport')->default('udp'); // udp, tcp, tls
            $table->string('auth_type')->default('ip'); // ip, registration
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('from_domain')->nullable();
            $table->string('from_user')->nullable();
            $table->json('codecs')->nullable();
            $table->integer('max_channels')->nullable();
            $table->string('context')->default('from-trunk');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carriers');
    }
};

