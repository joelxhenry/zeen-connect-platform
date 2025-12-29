<?php

namespace Database\Seeders;

use Database\Seeders\Development\TestUserSeeder;
use Illuminate\Database\Seeder;

class DevSeeder extends Seeder
{
    /**
     * Development-only seeders for test data.
     * NEVER run in production.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('DevSeeder cannot run in production!');

            return;
        }

        $this->call([
            TestUserSeeder::class,
        ]);
    }
}
