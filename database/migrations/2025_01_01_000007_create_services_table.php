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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('duration_minutes')->default(60);
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            // Booking Settings (service-level overrides)
            $table->boolean('use_provider_defaults')->default(true);
            $table->boolean('requires_approval')->nullable();
            $table->enum('deposit_type', ['none', 'fixed', 'percentage'])->nullable();
            $table->decimal('deposit_amount', 10, 2)->nullable();
            $table->enum('cancellation_policy', ['flexible', 'moderate', 'strict'])->nullable();
            $table->unsignedInteger('advance_booking_days')->nullable();
            $table->unsignedInteger('min_booking_notice_hours')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['provider_id', 'is_active']);
            $table->index(['category_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
