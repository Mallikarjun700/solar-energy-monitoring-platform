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
        $tenantId = '11111111-1111-4111-8111-111111111111';

        $users = [
            [
                'name' => 'Solar Administrator',
                'email' => 'admin@example.com',
                'role' => UserRole::ADMIN->value,
                'tenant_id' => $tenantId,
            ],
            [
                'name' => 'Solar Operator',
                'email' => 'operator@example.com',
                'role' => UserRole::OPERATOR->value,
                'tenant_id' => $tenantId,
            ],
            [
                'name' => 'Solar Viewer',
                'email' => 'viewer@example.com',
                'role' => UserRole::VIEWER->value,
                'tenant_id' => $tenantId,
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
