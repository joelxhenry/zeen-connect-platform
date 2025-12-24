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
        // Update existing values to match structure.md
        SystemSetting::set('free_tier_platform_fee_rate', 8, 'Platform fee percentage for free tier (8%)');
        SystemSetting::set('premium_tier_platform_fee_rate', 4, 'Platform fee percentage for premium tier (4%)');

        // Add missing tier pricing settings
        SystemSetting::set('premium_tier_monthly_price', 3500, 'Monthly subscription price for Premium tier (JMD)');
        SystemSetting::set('enterprise_tier_monthly_price', 20000, 'Monthly subscription price for Enterprise tier (JMD)');

        // Add minimum deposit amount for Free tier
        SystemSetting::set('minimum_deposit_amount', 500, 'Mandatory minimum deposit amount for Free tier (JMD)');

        // Add enterprise processing fee settings
        SystemSetting::set('enterprise_processing_fee_rate', 2.9, 'Card processing fee percentage for Enterprise tier');
        SystemSetting::set('enterprise_processing_fee_flat', 50, 'Flat card processing fee per transaction (JMD)');

        // Add no-show deposit split settings
        SystemSetting::set('no_show_deposit_provider_percentage', 50, 'Percentage of deposit kept by provider on no-show');
        SystemSetting::set('no_show_deposit_platform_percentage', 50, 'Percentage of deposit kept by platform on no-show');

        // Clear cache
        SystemSetting::clearAllCache();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original values
        SystemSetting::set('free_tier_platform_fee_rate', 10);
        SystemSetting::set('premium_tier_platform_fee_rate', 2);

        // Remove new settings
        SystemSetting::where('key', 'premium_tier_monthly_price')->delete();
        SystemSetting::where('key', 'enterprise_tier_monthly_price')->delete();
        SystemSetting::where('key', 'minimum_deposit_amount')->delete();
        SystemSetting::where('key', 'enterprise_processing_fee_rate')->delete();
        SystemSetting::where('key', 'enterprise_processing_fee_flat')->delete();
        SystemSetting::where('key', 'no_show_deposit_provider_percentage')->delete();
        SystemSetting::where('key', 'no_show_deposit_platform_percentage')->delete();

        SystemSetting::clearAllCache();
    }
};
