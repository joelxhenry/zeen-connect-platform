<?php

namespace Database\Seeders;

use Database\Seeders\Production\GatewaySeeder;
use Database\Seeders\Production\SystemSettingsSeeder;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Production-safe seeders that can run on every deployment.
     * All seeders must be idempotent (safe to run multiple times).
     */
    public function run(): void
    {
        $this->call([
            SystemSettingsSeeder::class,
            GatewaySeeder::class,
        ]);
    }
}
