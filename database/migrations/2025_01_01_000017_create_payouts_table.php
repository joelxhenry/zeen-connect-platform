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
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();

            // Payout details
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('JMD');

            // Period this payout covers
            $table->date('period_start');
            $table->date('period_end');

            // Bank/payment details
            $table->string('payout_method')->default('bank_transfer');
            $table->string('bank_name')->nullable();
            $table->string('bank_account_last_four')->nullable();
            $table->string('reference_number')->nullable();

            // Status
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();

            // Admin processing
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->index(['provider_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        // Pivot table to track which payments are included in a payout
        Schema::create('payment_payout', function (Blueprint $table) {
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payout_id')->constrained()->cascadeOnDelete();
            $table->primary(['payment_id', 'payout_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_payout');
        Schema::dropIfExists('payouts');
    }
};
