<?php

use App\Domains\Admin\Models\SystemSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        SystemSetting::set(
            'starter_tier_minimum_service_price',
            1000,
            'Minimum service price for Starter tier providers (JMD)'
        );

        SystemSetting::set(
            'premium_tier_minimum_service_price',
            500,
            'Minimum service price for Premium tier providers (JMD)'
        );

        SystemSetting::set(
            'enterprise_tier_minimum_service_price',
            0,
            'Minimum service price for Enterprise tier (JMD). Set to 0 for no minimum.'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        SystemSetting::where('key', 'starter_tier_minimum_service_price')->delete();
        SystemSetting::where('key', 'premium_tier_minimum_service_price')->delete();
        SystemSetting::where('key', 'enterprise_tier_minimum_service_price')->delete();
    }
};
