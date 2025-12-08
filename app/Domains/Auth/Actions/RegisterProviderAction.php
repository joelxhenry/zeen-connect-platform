<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Provider\Models\Provider;
use App\Domains\User\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterProviderAction
{
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'role' => UserRole::Provider,
            ]);

            Provider::create([
                'user_id' => $user->id,
                'business_name' => $data['business_name'],
                'bio' => $data['bio'] ?? null,
                'tagline' => $data['tagline'] ?? null,
                'location' => $data['location'] ?? null,
            ]);

            return $user;
        });
    }
}
