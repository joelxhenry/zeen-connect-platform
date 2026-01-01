<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Truncate categories table (we're starting fresh)
        // Services will be left uncategorized and providers will create their own categories
        // Disable FK checks for MySQL compatibility
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('categories')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Step 2: Drop the old unique constraint on slug
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['slug']);
        });

        // Step 3: Add new columns
        Schema::table('categories', function (Blueprint $table) {
            // Add provider_id (categories now belong to providers)
            $table->foreignId('provider_id')
                ->after('uuid')
                ->constrained()
                ->cascadeOnDelete();

            // Add type column for service vs event categories
            $table->string('type', 20)->after('provider_id')->default('service');

            // Update indexes
            $table->index(['provider_id', 'type', 'is_active', 'sort_order'], 'categories_provider_type_active_order');
            $table->unique(['provider_id', 'slug', 'type'], 'categories_provider_slug_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Drop new indexes
            $table->dropIndex('categories_provider_type_active_order');
            $table->dropUnique('categories_provider_slug_type_unique');

            // Drop foreign key and columns
            $table->dropForeign(['provider_id']);
            $table->dropColumn(['provider_id', 'type']);

            // Restore original unique constraint
            $table->unique('slug');
        });
    }
};
