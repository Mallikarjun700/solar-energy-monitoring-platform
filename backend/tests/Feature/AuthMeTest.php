<?php

namespace Tests\Feature;

use App\Enums\TokenAbility;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthMeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_current_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Solar Operator',
            'email' => 'admin@example.com',
            'role' => UserRole::OPERATOR,
        ]);

        $token = $user->createToken(
            'frontend',
            [
                TokenAbility::TELEMETRY_READ->value,
                TokenAbility::TELEMETRY_WRITE->value,
            ]
        )->plainTextToken;

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/auth/me');

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Authenticated user.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => 'Solar Operator',
                        'email' => 'admin@example.com',
                        'role' => 'operator',
                    ],
                    'abilities' => [
                        TokenAbility::TELEMETRY_READ->value,
                        TokenAbility::TELEMETRY_WRITE->value,
                    ],
                ],
            ]);
    }

    public function test_current_user_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertUnauthorized();
    }

    public function test_current_user_returns_only_current_token_abilities(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $token = $user->createToken(
            'restricted-token',
            [
                TokenAbility::TELEMETRY_READ->value,
            ]
        )->plainTextToken;

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/auth/me');

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.abilities',
                [
                    TokenAbility::TELEMETRY_READ->value,
                ]
            );
    }
}
