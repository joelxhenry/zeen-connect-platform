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
        Schema::table('services', function (Blueprint $table) {
            // Drop the foreign key constraint first (MySQL requires this before dropping index)
            $table->dropForeign(['category_id']);
        });

        Schema::table('services', function (Blueprint $table) {
            // Now drop the index
            $table->dropIndex(['category_id', 'is_active']);

            // Drop the column
            $table->dropColumn('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Re-add the category_id column
            // Note: This won't restore the data, just the structure
            $table->foreignId('category_id')
                ->after('provider_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->index(['category_id', 'is_active']);
        });
    }
};
