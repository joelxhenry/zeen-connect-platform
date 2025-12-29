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
        Schema::create('gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('escrow');
            $table->boolean('is_active')->default(true);
            $table->boolean('supports_split')->default(false);
            $table->boolean('supports_escrow')->default(true);
            $table->json('config')->nullable();
            $table->json('supported_currencies')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gateways');
    }
};
