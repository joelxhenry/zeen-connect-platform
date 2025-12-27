<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $settings = [
            // Gateway fee (payment processor passthrough)
            [
                'key' => 'gateway_fee_rate',
                'value' => json_encode(4.0),
                'description' => 'Payment gateway fee rate (percentage) passed through to providers/clients',
            ],
            // Zeen fee per tier
            [
                'key' => 'starter_zeen_fee_rate',
                'value' => json_encode(3.0),
                'description' => 'Zeen platform fee for Starter tier (percentage)',
            ],
            [
                'key' => 'premium_zeen_fee_rate',
                'value' => json_encode(1.5),
                'description' => 'Zeen platform fee for Premium tier (percentage)',
            ],
            [
                'key' => 'enterprise_zeen_fee_rate',
                'value' => json_encode(0.5),
                'description' => 'Zeen platform fee for Enterprise tier (percentage)',
            ],
            // Monthly prices per tier (JMD)
            [
                'key' => 'starter_monthly_price',
                'value' => json_encode(0),
                'description' => 'Monthly subscription price for Starter tier (JMD)',
            ],
            [
                'key' => 'premium_monthly_price',
                'value' => json_encode(4000),
                'description' => 'Monthly subscription price for Premium tier (JMD)',
            ],
            [
                'key' => 'enterprise_monthly_price',
                'value' => json_encode(15000),
                'description' => 'Monthly subscription price for Enterprise tier (JMD)',
            ],
            // Team slots per tier
            [
                'key' => 'starter_team_slots',
                'value' => json_encode(1),
                'description' => 'Team member slots for Starter tier (1 = owner only)',
            ],
            [
                'key' => 'premium_team_slots',
                'value' => json_encode(5),
                'description' => 'Team member slots for Premium tier',
            ],
        ];

        $now = now();

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'description' => $setting['description'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', [
            'gateway_fee_rate',
            'starter_zeen_fee_rate',
            'premium_zeen_fee_rate',
            'enterprise_zeen_fee_rate',
            'starter_monthly_price',
            'premium_monthly_price',
            'enterprise_monthly_price',
            'starter_team_slots',
            'premium_team_slots',
        ])->delete();
    }
};
