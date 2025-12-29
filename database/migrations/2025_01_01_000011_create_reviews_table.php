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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();

            // Rating (1-5 stars)
            $table->unsignedTinyInteger('rating');

            // Review content
            $table->text('comment')->nullable();
            $table->text('provider_response')->nullable();
            $table->timestamp('provider_responded_at')->nullable();

            // Moderation
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_flagged')->default(false);
            $table->text('flag_reason')->nullable();

            $table->timestamps();

            $table->index(['provider_id', 'is_visible']);
            $table->index(['client_id', 'created_at']);
            $table->index('rating');
            $table->unique('booking_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
