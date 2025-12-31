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
        // Step 1: Add the settings JSON column
        Schema::table('services', function (Blueprint $table) {
            $table->json('settings')->nullable()->after('sort_order');
        });

        // Step 2: Migrate existing data to JSON
        DB::table('services')->orderBy('id')->chunk(100, function ($services) {
            foreach ($services as $service) {
                DB::table('services')->where('id', $service->id)->update([
                    'settings' => json_encode([
                        'use_provider_defaults' => (bool) $service->use_provider_defaults,
                        'requires_approval' => $service->requires_approval,
                        'deposit_type' => $service->deposit_type,
                        'deposit_amount' => $service->deposit_amount ? (float) $service->deposit_amount : null,
                        'cancellation_policy' => $service->cancellation_policy,
                        'advance_booking_days' => $service->advance_booking_days,
                        'min_booking_notice_hours' => $service->min_booking_notice_hours,
                        'buffer_minutes' => $service->buffer_minutes,
                    ]),
                ]);
            }
        });

        // Step 3: Drop old columns
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'use_provider_defaults',
                'requires_approval',
                'deposit_type',
                'deposit_amount',
                'cancellation_policy',
                'advance_booking_days',
                'min_booking_notice_hours',
                'buffer_minutes',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Add back the individual columns
        Schema::table('services', function (Blueprint $table) {
            $table->boolean('use_provider_defaults')->default(true)->after('sort_order');
            $table->boolean('requires_approval')->nullable()->after('use_provider_defaults');
            $table->string('deposit_type')->nullable()->after('requires_approval');
            $table->decimal('deposit_amount', 10, 2)->nullable()->after('deposit_type');
            $table->string('cancellation_policy')->nullable()->after('deposit_amount');
            $table->unsignedInteger('advance_booking_days')->nullable()->after('cancellation_policy');
            $table->unsignedInteger('min_booking_notice_hours')->nullable()->after('advance_booking_days');
            $table->unsignedSmallInteger('buffer_minutes')->nullable()->after('min_booking_notice_hours');
        });

        // Step 2: Migrate data back from JSON
        DB::table('services')->orderBy('id')->chunk(100, function ($services) {
            foreach ($services as $service) {
                $settings = json_decode($service->settings, true) ?? [];
                DB::table('services')->where('id', $service->id)->update([
                    'use_provider_defaults' => $settings['use_provider_defaults'] ?? true,
                    'requires_approval' => $settings['requires_approval'] ?? null,
                    'deposit_type' => $settings['deposit_type'] ?? null,
                    'deposit_amount' => $settings['deposit_amount'] ?? null,
                    'cancellation_policy' => $settings['cancellation_policy'] ?? null,
                    'advance_booking_days' => $settings['advance_booking_days'] ?? null,
                    'min_booking_notice_hours' => $settings['min_booking_notice_hours'] ?? null,
                    'buffer_minutes' => $settings['buffer_minutes'] ?? null,
                ]);
            }
        });

        // Step 3: Drop the settings column
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
