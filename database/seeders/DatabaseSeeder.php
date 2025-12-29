<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Always run production seeders (idempotent - safe to run multiple times)
        $this->call(ProductionSeeder::class);

        // Only run dev seeder in non-production environments
        if (app()->environment(['local', 'development', 'testing'])) {
            $this->call(DevSeeder::class);
        }
    }
}
