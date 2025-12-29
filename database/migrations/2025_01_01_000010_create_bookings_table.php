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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('client_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();

            // Guest booking fields
            $table->string('guest_email')->nullable();
            $table->string('guest_name')->nullable();
            $table->string('guest_phone', 20)->nullable();

            // Scheduling
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status')->default('pending');

            // Pricing
            $table->decimal('service_price', 10, 2);
            $table->decimal('platform_fee', 10, 2)->default(0);
            $table->decimal('zeen_fee', 10, 2)->nullable();
            $table->decimal('gateway_fee', 10, 2)->nullable();
            $table->decimal('convenience_fee', 10, 2)->nullable();
            $table->enum('fee_payer', ['provider', 'client'])->default('provider');
            $table->decimal('total_amount', 10, 2);

            // Deposit
            $table->decimal('deposit_amount', 10, 2)->nullable();
            $table->boolean('deposit_paid')->default(false);
            $table->decimal('platform_fee_amount', 10, 2)->nullable();
            $table->decimal('processing_fee_amount', 10, 2)->nullable();

            // Notes
            $table->text('client_notes')->nullable();
            $table->text('provider_notes')->nullable();
            $table->text('cancellation_reason')->nullable();

            // Timestamps
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();

            $table->index(['provider_id', 'booking_date']);
            $table->index(['client_id', 'status']);
            $table->index(['booking_date', 'start_time']);
            $table->index('guest_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
