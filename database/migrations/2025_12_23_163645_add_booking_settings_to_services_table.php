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
        Schema::table('services', function (Blueprint $table) {
            $table->boolean('use_provider_defaults')->default(true)->after('sort_order');
            $table->boolean('requires_approval')->nullable()->after('use_provider_defaults');
            $table->enum('deposit_type', ['none', 'fixed', 'percentage'])->nullable()->after('requires_approval');
            $table->decimal('deposit_amount', 10, 2)->nullable()->after('deposit_type');
            $table->enum('cancellation_policy', ['flexible', 'moderate', 'strict'])->nullable()->after('deposit_amount');
            $table->unsignedInteger('advance_booking_days')->nullable()->after('cancellation_policy');
            $table->unsignedInteger('min_booking_notice_hours')->nullable()->after('advance_booking_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'use_provider_defaults',
                'requires_approval',
                'deposit_type',
                'deposit_amount',
                'cancellation_policy',
                'advance_booking_days',
                'min_booking_notice_hours',
            ]);
        });
    }
};
