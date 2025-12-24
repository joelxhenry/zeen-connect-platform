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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('system_settings')->insert([
            [
                'key' => 'minimum_deposit_percentage',
                'value' => json_encode(15),
                'description' => 'Minimum deposit percentage for premium tier providers',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'free_tier_deposit_percentage',
                'value' => json_encode(20),
                'description' => 'Fixed deposit percentage for free tier providers',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'free_tier_platform_fee_rate',
                'value' => json_encode(10),
                'description' => 'Platform fee percentage for free tier (10%)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'premium_tier_platform_fee_rate',
                'value' => json_encode(2),
                'description' => 'Platform fee percentage for premium tier (2%)',
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
        Schema::dropIfExists('system_settings');
    }
};
