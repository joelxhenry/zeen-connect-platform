<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Client\Models\Client;
use App\Domains\User\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Arr;

class RegisterUserAction
{
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => Arr::get($data, 'name'),
                'email' => Arr::get($data, 'email'),
                'password' => Arr::get($data, 'password'),
                'phone' => Arr::get($data, 'phone'),
                'role' => Arr::get($data, 'role', UserRole::Client),
            ]);

            // Create client profile for client users
            if ($user->role === UserRole::Client) {
                Client::create([
                    'user_id' => $user->id,
                ]);
            }

            return $user;
        });
    }
}
