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
        DB::table('system_settings')->insert([
            [
                'key' => 'extra_team_member_monthly_fee',
                'value' => json_encode(1000),
                'description' => 'Monthly fee for each additional team member beyond free slots (in kobo/cents)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'premium_tier_free_team_members',
                'value' => json_encode(3),
                'description' => 'Number of free team members for premium tier',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('system_settings')
            ->whereIn('key', [
                'extra_team_member_monthly_fee',
                'premium_tier_free_team_members',
            ])
            ->delete();
    }
};
