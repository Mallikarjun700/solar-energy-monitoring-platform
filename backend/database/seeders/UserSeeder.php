<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Solar Administrator',
                'email' => 'admin@example.com',
                'role' => UserRole::ADMIN->value,
            ],
            [
                'name' => 'Solar Operator',
                'email' => 'operator@example.com',
                'role' => UserRole::OPERATOR->value,
            ],
            [
                'name' => 'Solar Viewer',
                'email' => 'viewer@example.com',
                'role' => UserRole::VIEWER->value,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    ...$user,
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ]
            );
        }
    }
}