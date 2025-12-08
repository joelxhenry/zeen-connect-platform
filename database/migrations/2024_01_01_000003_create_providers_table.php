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
            $table->foreignId('primary_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('business_name');
            $table->string('slug')->unique();
            $table->text('bio')->nullable();
            $table->string('tagline')->nullable();
            $table->string('address')->nullable(); // Street address details
            $table->string('website')->nullable();
            $table->json('social_links')->nullable();
            $table->enum('status', ['pending', 'active', 'suspended', 'inactive'])->default('pending');
            $table->decimal('commission_rate', 5, 2)->default(15.00);
            $table->decimal('rating_avg', 3, 2)->default(0.00);
            $table->unsignedInteger('rating_count')->default(0);
            $table->unsignedInteger('total_bookings')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_featured']);
            $table->index('rating_avg');
            $table->index('primary_location_id');
        });

        // Pivot table for providers serving multiple locations
        Schema::create('location_provider', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['provider_id', 'location_id']);
            $table->index('location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_provider');
        Schema::dropIfExists('providers');
    }
};
