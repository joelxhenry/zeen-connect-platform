<?php

namespace Database\Seeders\Development;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestUserSeeder extends Seeder
{
    /**
     * Seed test users using updateOrInsert for idempotency.
     */
    public function run(): void
    {
        $users = $this->getUsers();
        $now = now();

        foreach ($users as $userData) {
            // Check if user exists to preserve their UUID
            $existing = DB::table('users')->where('email', $userData['email'])->first();

            DB::table('users')->updateOrInsert(
                ['email' => $userData['email']],
                [
                    'uuid' => $existing?->uuid ?? Str::uuid()->toString(),
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'role' => $userData['role'],
                    'email_verified_at' => $now,
                    'is_active' => true,
                    'updated_at' => $now,
                ]
            );
        }

        $this->command->info('Test users seeded: '.count($users).' users');
    }

    private function getUsers(): array
    {
        return [
            [
                'name' => 'Test Admin',
                'email' => 'admin@example.com',
                'role' => 'admin',
            ],
            [
                'name' => 'Test Provider',
                'email' => 'provider@example.com',
                'role' => 'provider',
            ],
            [
                'name' => 'Test Client',
                'email' => 'client@example.com',
                'role' => 'client',
            ],
        ];
    }
}
