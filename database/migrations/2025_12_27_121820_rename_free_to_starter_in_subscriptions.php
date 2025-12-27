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
        // Update existing subscriptions from 'free' to 'starter'
        DB::table('subscriptions')
            ->where('tier', 'free')
            ->update(['tier' => 'starter']);

        // Update related system settings keys
        DB::table('system_settings')
            ->where('key', 'like', '%free_tier%')
            ->get()
            ->each(function ($setting) {
                $newKey = str_replace('free_tier', 'starter_tier', $setting->key);
                DB::table('system_settings')
                    ->where('id', $setting->id)
                    ->update(['key' => $newKey]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert subscriptions from 'starter' back to 'free'
        DB::table('subscriptions')
            ->where('tier', 'starter')
            ->update(['tier' => 'free']);

        // Revert system settings keys
        DB::table('system_settings')
            ->where('key', 'like', '%starter_tier%')
            ->get()
            ->each(function ($setting) {
                $newKey = str_replace('starter_tier', 'free_tier', $setting->key);
                DB::table('system_settings')
                    ->where('id', $setting->id)
                    ->update(['key' => $newKey]);
            });
    }
};
