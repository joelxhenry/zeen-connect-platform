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
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Business Info
            $table->string('business_name');
            $table->string('slug')->unique();
            $table->string('domain')->unique();
            $table->text('bio')->nullable();
            $table->string('tagline')->nullable();
            $table->string('address')->nullable();
            $table->string('website')->nullable();
            $table->json('social_links')->nullable();

            // Status & Ratings
            $table->enum('status', ['pending', 'active', 'suspended', 'inactive'])->default('pending');
            $table->decimal('commission_rate', 5, 2)->default(15.00);
            $table->decimal('rating_avg', 3, 2)->default(0.00);
            $table->unsignedInteger('rating_count')->default(0);
            $table->unsignedInteger('total_bookings')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_founding_member')->default(false);
            $table->timestamp('founding_member_at')->nullable();

            // Booking Settings
            $table->boolean('requires_approval')->default(false);
            $table->enum('deposit_type', ['none', 'fixed', 'percentage'])->default('none');
            $table->decimal('deposit_amount', 10, 2)->nullable();
            $table->decimal('deposit_percentage', 5, 2)->nullable();
            $table->enum('cancellation_policy', ['flexible', 'moderate', 'strict'])->default('flexible');
            $table->unsignedInteger('advance_booking_days')->default(30);
            $table->unsignedInteger('min_booking_notice_hours')->default(24);
            $table->enum('fee_payer', ['provider', 'client'])->default('provider');

            // Banking Info
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_holder_name')->nullable();
            $table->string('bank_branch_code')->nullable();
            $table->enum('bank_account_type', ['savings', 'checking'])->nullable();
            $table->boolean('banking_info_verified')->default(false);
            $table->timestamp('banking_info_verified_at')->nullable();

            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_featured']);
            $table->index('rating_avg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
