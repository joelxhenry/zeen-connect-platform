<?php

namespace App\Console\Commands;

use Database\Seeders\ProductionSeeder;
use Database\Seeders\Sandbox\SandboxSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class SandboxSeedCommand extends Command
{
    protected $signature = 'db:sandbox
                            {--fresh : Wipe the database before seeding}
                            {--providers=40 : Number of providers to create}
                            {--clients=100 : Number of clients to create}';

    protected $description = 'Seed the database with sandbox data for development and testing';

    public function handle(): int
    {
        // Block production environment
        if (App::environment('production')) {
            $this->error('This command cannot be run in production!');
            return self::FAILURE;
        }

        $this->info('');
        $this->info('  ╔═══════════════════════════════════════════╗');
        $this->info('  ║       Zeen Connect Sandbox Seeder         ║');
        $this->info('  ╚═══════════════════════════════════════════╝');
        $this->info('');

        $providers = (int) $this->option('providers');
        $clients = (int) $this->option('clients');

        $this->info("Configuration:");
        $this->info("  • Providers: {$providers}");
        $this->info("  • Clients: {$clients}");
        $this->info("  • Fresh: " . ($this->option('fresh') ? 'Yes' : 'No'));
        $this->info('');

        if (! $this->confirm('Do you want to proceed with sandbox seeding?', true)) {
            $this->info('Sandbox seeding cancelled.');
            return self::SUCCESS;
        }

        // Run fresh migration if requested
        if ($this->option('fresh')) {
            $this->newLine();
            $this->warn('Running fresh migration...');
            $this->call('migrate:fresh', ['--force' => true]);
        }

        // Run production seeder first (system data like industries, categories)
        $this->newLine();
        $this->info('Running production seeder (system data)...');
        $this->call('db:seed', [
            '--class' => ProductionSeeder::class,
            '--force' => true,
        ]);

        // Run sandbox seeder
        $this->newLine();
        $this->info('Running sandbox seeder...');

        // Store options in config for seeder to access
        config([
            'sandbox.providers' => $providers,
            'sandbox.clients' => $clients,
        ]);

        $this->call('db:seed', [
            '--class' => SandboxSeeder::class,
            '--force' => true,
        ]);

        $this->newLine();
        $this->info('  ╔═══════════════════════════════════════════╗');
        $this->info('  ║     Sandbox seeding completed!            ║');
        $this->info('  ╚═══════════════════════════════════════════╝');
        $this->newLine();

        $this->table(
            ['Entity', 'Count'],
            [
                ['Providers', $providers],
                ['Clients', $clients],
            ]
        );

        $this->newLine();
        $this->info('Test accounts:');
        $this->info('  • Admin: admin@example.com / password');
        $this->info('  • Starter Provider: starter@example.com / password');
        $this->info('  • Premium Provider: premium@example.com / password');
        $this->info('  • Enterprise Provider: enterprise@example.com / password');
        $this->newLine();

        return self::SUCCESS;
    }
}
