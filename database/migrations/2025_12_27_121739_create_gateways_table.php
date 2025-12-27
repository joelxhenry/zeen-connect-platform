<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->string('type')->default('escrow'); // split, escrow
            $table->boolean('is_active')->default(true);
            $table->boolean('supports_split')->default(false);
            $table->boolean('supports_escrow')->default(true);
            $table->json('config')->nullable();
            $table->json('supported_currencies')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'type']);
        });

        // Seed default gateways
        DB::table('gateways')->insert([
            [
                'name' => 'PowerTranz',
                'slug' => 'powertranz',
                'type' => 'escrow',
                'is_active' => true,
                'supports_split' => false,
                'supports_escrow' => true,
                'supported_currencies' => json_encode(['JMD', 'USD', 'TTD', 'BBD', 'XCD']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'WiPay',
                'slug' => 'wipay',
                'type' => 'escrow',
                'is_active' => true,
                'supports_split' => true,
                'supports_escrow' => true,
                'supported_currencies' => json_encode(['JMD', 'TTD', 'USD']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Fygaro',
                'slug' => 'fygaro',
                'type' => 'escrow',
                'is_active' => true,
                'supports_split' => true,
                'supports_escrow' => true,
                'supported_currencies' => json_encode(['JMD', 'USD']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gateways');
    }
};
