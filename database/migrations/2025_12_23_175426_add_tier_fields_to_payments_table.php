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
        // Only add columns if they don't exist (for partial migration recovery)
        if (! Schema::hasColumn('payments', 'payment_type')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->enum('payment_type', ['full', 'deposit', 'balance'])->default('full')->after('currency');
            });
        }

        if (! Schema::hasColumn('payments', 'processing_fee')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->decimal('processing_fee', 10, 2)->nullable()->after('provider_amount');
            });
        }

        if (! Schema::hasColumn('payments', 'processing_fee_payer')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->enum('processing_fee_payer', ['client', 'provider'])->nullable()->after('processing_fee');
            });
        }

        if (! Schema::hasColumn('payments', 'is_refunded')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->boolean('is_refunded')->default(false)->after('status');
            });
        }

        if (! Schema::hasColumn('payments', 'refund_reason')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('refund_reason')->nullable()->after('is_refunded');
            });
        }

        if (! Schema::hasColumn('payments', 'refund_transaction_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('refund_transaction_id')->nullable()->after('refund_reason');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'payment_type',
                'processing_fee',
                'processing_fee_payer',
                'is_refunded',
                'refund_reason',
                'refund_transaction_id',
            ]);
        });
    }
};
