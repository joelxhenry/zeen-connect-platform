<?php

namespace Database\Seeders\Sandbox;

use Illuminate\Database\Seeder;

class SandboxSeeder extends Seeder
{
    /**
     * Run the sandbox seeders.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('Starting sandbox data seeding...');
        $this->command->info('');

        // Get configuration from config (set by command) or use defaults
        $providerCount = config('sandbox.providers', 40);
        $clientCount = config('sandbox.clients', 100);

        // Store counts for child seeders
        config(['sandbox.providers' => $providerCount]);
        config(['sandbox.clients' => $clientCount]);

        // Run seeders in order (respecting dependencies)
        $seeders = [
            [SandboxIndustrySeeder::class, 'Industries & Categories'],
            [SandboxUserSeeder::class, 'Users (Admin + Clients)'],
            [SandboxProviderSeeder::class, 'Providers & Subscriptions'],
            [SandboxServiceSeeder::class, 'Services'],
            [SandboxTeamMemberSeeder::class, 'Team Members'],
            [SandboxAvailabilitySeeder::class, 'Provider Availability'],
            [SandboxBookingSeeder::class, 'Bookings & Payments'],
            [SandboxReviewSeeder::class, 'Reviews'],
            [SandboxEventSeeder::class, 'Events'],
        ];

        $bar = $this->command->getOutput()->createProgressBar(count($seeders));
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- %message%');

        foreach ($seeders as [$seeder, $description]) {
            $bar->setMessage($description);
            $this->call($seeder);
            $bar->advance();
        }

        $bar->setMessage('Complete!');
        $bar->finish();

        $this->command->newLine(2);
        $this->command->info('Sandbox data seeded successfully!');
    }
}
