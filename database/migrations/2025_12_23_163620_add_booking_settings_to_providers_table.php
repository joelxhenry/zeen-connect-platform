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
            $table->boolean('requires_approval')->default(false)->after('is_featured');
            $table->enum('deposit_type', ['none', 'fixed', 'percentage'])->default('none')->after('requires_approval');
            $table->decimal('deposit_amount', 10, 2)->nullable()->after('deposit_type');
            $table->enum('cancellation_policy', ['flexible', 'moderate', 'strict'])->default('flexible')->after('deposit_amount');
            $table->unsignedInteger('advance_booking_days')->default(30)->after('cancellation_policy');
            $table->unsignedInteger('min_booking_notice_hours')->default(24)->after('advance_booking_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn([
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
