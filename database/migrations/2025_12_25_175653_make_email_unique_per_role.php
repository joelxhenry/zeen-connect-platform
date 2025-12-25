<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Changes email uniqueness from global to per-role.
     * This allows the same email to exist for different roles
     * (e.g., john@example.com as both Client and Provider).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the existing unique constraint on email
            $table->dropUnique(['email']);

            // Add composite unique constraint on email + role
            $table->unique(['email', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique(['email', 'role']);

            // Restore the original unique constraint on email only
            $table->unique('email');
        });
    }
};
