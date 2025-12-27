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
        Schema::table('bookings', function (Blueprint $table) {
            // Add separated fee columns after platform_fee
            $table->decimal('zeen_fee', 10, 2)->nullable()->after('platform_fee');
            $table->decimal('gateway_fee', 10, 2)->nullable()->after('zeen_fee');
            $table->decimal('convenience_fee', 10, 2)->nullable()->after('gateway_fee');

            // Track who pays the fees for this specific booking
            $table->enum('fee_payer', ['provider', 'client'])->default('provider')->after('convenience_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'zeen_fee',
                'gateway_fee',
                'convenience_fee',
                'fee_payer',
            ]);
        });
    }
};
