<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Google OAuth fields
            $table->string('google_id')->nullable()->unique()->after('avatar');
            $table->timestamp('google_linked_at')->nullable()->after('google_id');

            // Apple OAuth fields
            $table->string('apple_id')->nullable()->unique()->after('google_linked_at');
            $table->timestamp('apple_linked_at')->nullable()->after('apple_id');

            // Make password nullable for social-only users
            $table->string('password')->nullable()->change();

            // Indexes for faster lookups
            $table->index(['google_id']);
            $table->index(['apple_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['google_id']);
            $table->dropIndex(['apple_id']);
            $table->dropColumn([
                'google_id',
                'google_linked_at',
                'apple_id',
                'apple_linked_at',
            ]);
            $table->string('password')->nullable(false)->change();
        });
    }
};
