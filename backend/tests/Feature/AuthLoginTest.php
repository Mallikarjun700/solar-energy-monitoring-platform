<?php

namespace Tests\Feature;

use App\Enums\TokenAbility;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'name' => 'Solar Operator',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::OPERATOR,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                    ],
                    'token',
                    'abilities',
                ],
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'Login successful.',
                'data' => [
                    'user' => [
                        'name' => 'Solar Operator',
                        'email' => 'admin@example.com',
                        'role' => 'operator',
                    ],
                ],
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $token = PersonalAccessToken::first();

        $this->assertNotNull($token);
        $this->assertSame($user->id, $token->tokenable_id);
    }

    public function test_login_rejects_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'operator@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'operator@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid credentials.',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_rejects_unknown_user(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'unknown@example.com',
            'password' => 'password',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid credentials.',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response
            ->assertUnprocessable()
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email',
                    'password',
                ],
            ]);
    }

    public function test_login_assigns_operator_abilities(): void
    {
        User::factory()->create([
            'email' => 'operator@example.com',
            'password' => 'password',
            'role' => UserRole::OPERATOR,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'operator@example.com',
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.abilities',
                [
                    TokenAbility::TELEMETRY_READ->value,
                    TokenAbility::TELEMETRY_WRITE->value,
                    TokenAbility::ALERTS_READ->value,
                    TokenAbility::ALERTS_ACKNOWLEDGE->value,
                    TokenAbility::ALERTS_RESOLVE->value,
                ]
            );
    }

    public function test_viewer_does_not_receive_privileged_abilities(): void
    {
        User::factory()->create([
            'email' => 'viewer@example.com',
            'password' => 'password',
            'role' => UserRole::VIEWER,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'viewer@example.com',
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.abilities',
                [
                    TokenAbility::TELEMETRY_READ->value,
                    TokenAbility::ALERTS_READ->value,
                ]
            );
    }
}
