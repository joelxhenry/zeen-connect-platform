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
            // Guest booking fields
            $table->string('guest_email')->nullable()->after('client_id');
            $table->string('guest_name')->nullable()->after('guest_email');
            $table->string('guest_phone', 20)->nullable()->after('guest_name');

            // Deposit tracking
            $table->decimal('deposit_amount', 10, 2)->nullable()->after('total_amount');
            $table->boolean('deposit_paid')->default(false)->after('deposit_amount');

            // Fee tracking
            $table->decimal('platform_fee_amount', 10, 2)->nullable()->after('deposit_paid');
            $table->decimal('processing_fee_amount', 10, 2)->nullable()->after('platform_fee_amount');

            // Index for guest email lookups
            $table->index('guest_email');
        });

        // Make client_id nullable for guest bookings
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['guest_email']);
            $table->dropColumn([
                'guest_email',
                'guest_name',
                'guest_phone',
                'deposit_amount',
                'deposit_paid',
                'platform_fee_amount',
                'processing_fee_amount',
            ]);
        });
    }
};
