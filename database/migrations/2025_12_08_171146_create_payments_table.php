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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();

            // Amounts
            $table->decimal('amount', 10, 2);
            $table->decimal('platform_fee', 10, 2)->default(0);
            $table->decimal('provider_amount', 10, 2);
            $table->string('currency', 3)->default('JMD');

            // Payment gateway details
            $table->string('gateway')->default('powertranz');
            $table->string('gateway_transaction_id')->nullable();
            $table->string('gateway_order_id')->nullable();
            $table->string('gateway_response_code')->nullable();
            $table->text('gateway_response')->nullable();

            // Status
            $table->string('status')->default('pending');
            $table->string('failure_reason')->nullable();

            // Card details (masked)
            $table->string('card_last_four')->nullable();
            $table->string('card_brand')->nullable();

            // Timestamps
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['booking_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index(['provider_id', 'status']);
            $table->index('gateway_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
