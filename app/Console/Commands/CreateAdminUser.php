<?php

namespace App\Console\Commands;

use App\Domains\User\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Create a new admin user interactively';

    public function handle(): int
    {
        $this->info('');
        $this->info('  Creating Admin User');
        $this->info('  ====================');
        $this->info('');

        // Get name
        $name = $this->ask('Name');
        if (empty($name)) {
            $this->error('Name is required.');

            return Command::FAILURE;
        }

        // Get email
        $email = $this->askWithValidation(
            'Email',
            ['required', 'email', 'unique:users'],
            [
                'required' => 'Email is required.',
                'email' => 'Please enter a valid email address.',
                'unique' => 'An account with this email already exists.',
            ]
        );

        if ($email === null) {
            return Command::FAILURE;
        }

        // Get password
        $password = $this->secret('Password (min 8 characters)');
        if (empty($password) || strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return Command::FAILURE;
        }

        // Confirm password
        $passwordConfirm = $this->secret('Confirm Password');
        if ($password !== $passwordConfirm) {
            $this->error('Passwords do not match.');

            return Command::FAILURE;
        }

        // Confirm creation
        $this->info('');
        $this->info('  Summary:');
        $this->info("  Name:  {$name}");
        $this->info("  Email: {$email}");
        $this->info("  Role:  Administrator");
        $this->info('');

        if (! $this->confirm('Create this admin user?', true)) {
            $this->info('Cancelled.');

            return Command::SUCCESS;
        }

        // Create the admin user
        try {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => UserRole::Admin,
                'is_active' => true,
            ]);

            $this->info('');
            $this->info('  Admin user created successfully!');
            $this->info("  ID: {$user->uuid}");
            $this->info('');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to create admin user: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Ask for input with validation.
     */
    private function askWithValidation(string $question, array $rules, array $messages = []): ?string
    {
        $value = $this->ask($question);

        $validator = Validator::make(
            ['value' => $value],
            ['value' => $rules],
            collect($messages)->mapWithKeys(fn ($msg, $key) => ["value.{$key}" => $msg])->toArray()
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return null;
        }

        return $value;
    }
}
