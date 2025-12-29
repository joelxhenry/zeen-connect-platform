<?php

namespace Database\Seeders\Production;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Seed system settings using updateOrInsert for idempotency.
     */
    public function run(): void
    {
        $settings = $this->getSettings();
        $now = now();

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'value' => json_encode($setting['value']),
                    'description' => $setting['description'],
                    'updated_at' => $now,
                ]
            );
        }

        $this->command->info('System settings seeded: '.count($settings).' settings');
    }

    private function getSettings(): array
    {
        return [
            // Launch Mode
            [
                'key' => 'launch_mode_enabled',
                'value' => false,
                'description' => 'When enabled, registration is disabled and users are directed to the waitlist',
            ],

            // Deposit Settings
            [
                'key' => 'minimum_deposit_percentage',
                'value' => 15,
                'description' => 'Minimum deposit percentage for premium tier providers',
            ],
            [
                'key' => 'starter_tier_deposit_percentage',
                'value' => 20,
                'description' => 'Fixed deposit percentage for starter tier providers',
            ],
            [
                'key' => 'minimum_deposit_amount',
                'value' => 500,
                'description' => 'Mandatory minimum deposit amount for Starter tier (JMD)',
            ],

            // Gateway Fee (passthrough)
            [
                'key' => 'gateway_fee_rate',
                'value' => 4.0,
                'description' => 'Payment gateway fee rate (percentage) passed through to providers/clients',
            ],

            // Zeen Platform Fees per Tier
            [
                'key' => 'starter_zeen_fee_rate',
                'value' => 3.0,
                'description' => 'Zeen platform fee for Starter tier (percentage)',
            ],
            [
                'key' => 'premium_zeen_fee_rate',
                'value' => 1.5,
                'description' => 'Zeen platform fee for Premium tier (percentage)',
            ],
            [
                'key' => 'enterprise_zeen_fee_rate',
                'value' => 0.5,
                'description' => 'Zeen platform fee for Enterprise tier (percentage)',
            ],

            // Legacy Platform Fee Settings (for backwards compatibility)
            [
                'key' => 'starter_tier_platform_fee_rate',
                'value' => 8,
                'description' => 'Platform fee percentage for starter tier (8%)',
            ],
            [
                'key' => 'premium_tier_platform_fee_rate',
                'value' => 4,
                'description' => 'Platform fee percentage for premium tier (4%)',
            ],

            // Monthly Subscription Prices (JMD)
            [
                'key' => 'starter_monthly_price',
                'value' => 0,
                'description' => 'Monthly subscription price for Starter tier (JMD)',
            ],
            [
                'key' => 'premium_monthly_price',
                'value' => 4000,
                'description' => 'Monthly subscription price for Premium tier (JMD)',
            ],
            [
                'key' => 'enterprise_monthly_price',
                'value' => 15000,
                'description' => 'Monthly subscription price for Enterprise tier (JMD)',
            ],

            // Minimum Service Prices per Tier
            [
                'key' => 'starter_tier_minimum_service_price',
                'value' => 1000,
                'description' => 'Minimum service price for Starter tier providers (JMD)',
            ],
            [
                'key' => 'premium_tier_minimum_service_price',
                'value' => 500,
                'description' => 'Minimum service price for Premium tier providers (JMD)',
            ],
            [
                'key' => 'enterprise_tier_minimum_service_price',
                'value' => 0,
                'description' => 'Minimum service price for Enterprise tier (JMD). Set to 0 for no minimum.',
            ],

            // Team Member Settings
            [
                'key' => 'starter_team_slots',
                'value' => 1,
                'description' => 'Team member slots for Starter tier (1 = owner only)',
            ],
            [
                'key' => 'premium_team_slots',
                'value' => 5,
                'description' => 'Team member slots for Premium tier',
            ],
            [
                'key' => 'premium_tier_free_team_members',
                'value' => 3,
                'description' => 'Number of free team members for premium tier',
            ],
            [
                'key' => 'extra_team_member_monthly_fee',
                'value' => 1000,
                'description' => 'Monthly fee for each additional team member beyond free slots (JMD)',
            ],

            // Enterprise Processing Fee
            [
                'key' => 'enterprise_processing_fee_rate',
                'value' => 2.9,
                'description' => 'Card processing fee percentage for Enterprise tier',
            ],
            [
                'key' => 'enterprise_processing_fee_flat',
                'value' => 50,
                'description' => 'Flat card processing fee per transaction (JMD)',
            ],

            // No-Show Deposit Split
            [
                'key' => 'no_show_deposit_provider_percentage',
                'value' => 50,
                'description' => 'Percentage of deposit kept by provider on no-show',
            ],
            [
                'key' => 'no_show_deposit_platform_percentage',
                'value' => 50,
                'description' => 'Percentage of deposit kept by platform on no-show',
            ],
        ];
    }
}
