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
        Schema::create('provider_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('powertranz_token'); // Tokenized card
            $table->string('card_brand')->nullable(); // visa, mastercard, amex, etc.
            $table->string('card_last_four', 4);
            $table->string('card_expiry', 7); // MM/YYYY format
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['provider_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_payment_methods');
    }
};
