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
        // Drop pivot table first (has foreign keys to both providers and locations)
        Schema::dropIfExists('location_provider');

        // Remove primary_location_id from providers table
        Schema::table('providers', function (Blueprint $table) {
            $table->dropIndex(['primary_location_id']);
            $table->dropForeign(['primary_location_id']);
            $table->dropColumn('primary_location_id');
        });

        // Drop location tables in order (respecting foreign key constraints)
        Schema::dropIfExists('locations');
        Schema::dropIfExists('regions');
        Schema::dropIfExists('countries');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate countries table
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 100);
            $table->char('code', 2)->unique();
            $table->char('currency_code', 3);
            $table->string('timezone', 50);
            $table->string('phone_code', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('is_active');
        });

        // Recreate regions table
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['country_id', 'is_active']);
        });

        // Recreate locations table
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 120);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['region_id', 'slug']);
            $table->index(['region_id', 'is_active']);
        });

        // Add back primary_location_id to providers
        Schema::table('providers', function (Blueprint $table) {
            $table->foreignId('primary_location_id')->nullable()->after('user_id')->constrained('locations')->nullOnDelete();
            $table->index('primary_location_id');
        });

        // Recreate pivot table
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
};
