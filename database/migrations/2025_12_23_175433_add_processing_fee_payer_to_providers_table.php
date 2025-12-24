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
        Schema::table('providers', function (Blueprint $table) {
            // Processing fee payer choice for enterprise tier
            $table->enum('processing_fee_payer', ['client', 'provider'])->default('client')->after('min_booking_notice_hours');

            // Custom deposit percentage for premium tier (overrides minimum)
            $table->decimal('deposit_percentage', 5, 2)->nullable()->after('processing_fee_payer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn([
                'processing_fee_payer',
                'deposit_percentage',
            ]);
        });
    }
};
