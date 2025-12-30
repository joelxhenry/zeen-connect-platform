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
        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('tier'); // Snapshot of tier at time of invoice
            $table->string('billing_cycle'); // monthly, annual
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('JMD');
            $table->string('status'); // pending, paid, failed, refunded
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->string('powertranz_transaction_id')->nullable();
            $table->json('powertranz_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->integer('retry_count')->default(0);
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['provider_id', 'status']);
            $table->index(['subscription_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_invoices');
    }
};
