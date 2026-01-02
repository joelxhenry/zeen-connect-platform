<?php

namespace Database\Seeders\Sandbox;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SandboxUserSeeder extends Seeder
{
    /**
     * Seed admin and client users.
     */
    public function run(): void
    {
        $clientCount = config('sandbox.clients', 100);

        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'uuid' => Str::uuid()->toString(),
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // Create test client users
        $testClients = [
            ['email' => 'client@example.com', 'name' => 'Test Client'],
            ['email' => 'jane@example.com', 'name' => 'Jane Doe'],
            ['email' => 'john@example.com', 'name' => 'John Smith'],
        ];

        foreach ($testClients as $client) {
            User::firstOrCreate(
                ['email' => $client['email']],
                [
                    'uuid' => Str::uuid()->toString(),
                    'name' => $client['name'],
                    'password' => Hash::make('password'),
                    'role' => 'client',
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );
        }

        // Create random client users (minus the test clients we already created)
        $remainingClients = max(0, $clientCount - count($testClients));

        if ($remainingClients > 0) {
            User::factory()
                ->count($remainingClients)
                ->create([
                    'role' => 'client',
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]);
        }
    }
}
