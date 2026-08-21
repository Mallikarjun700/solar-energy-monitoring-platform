<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_telemetry_api_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/telemetry/events', [
            'events' => [],
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_access_telemetry_api(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('telemetry-test',[\App\Enums\TokenAbility::TELEMETRY_WRITE->value])->plainTextToken;

        $response = $this
            ->withToken($token)
            ->postJson('/api/v1/telemetry/events', [
                'events' => [
                    [
                        'event_id' => '550e8400-e29b-41d4-a716-446655440000',
                        'tenant_id' => '550e8400-e29b-41d4-a716-446655440001',
                        'source_id' => '550e8400-e29b-41d4-a716-446655440002',
                        'event_type' => 'telemetry.power',
                        'timestamp' => now()->toISOString(),
                        'schema_version' => 1,
                        'attributes' => [
                            'device_id' => 1,
                        ],
                        'payload' => [
                            'power_kw' => 52.5,
                        ],
                    ],
                ],
            ]);

        $response->assertStatus(202);
    }

    public function test_readiness_endpoint_remains_public(): void
    {
        $response = $this->getJson('/api/v1/ready');

        $response->assertSuccessful();
    }

    public function test_authenticated_user_without_telemetry_write_ability_is_forbidden(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken(
            'read-only-token',
            [\App\Enums\TokenAbility::TELEMETRY_READ->value]
        )->plainTextToken;

        $response = $this
            ->withToken($token)
            ->postJson('/api/v1/telemetry/events', [
                'events' => [],
            ]);

        $response->assertForbidden();
    }

    public function test_telemetry_token_cannot_read_dlq(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken(
            'telemetry-token',
            [\App\Enums\TokenAbility::TELEMETRY_WRITE->value]
        )->plainTextToken;

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/dlq');

        $response->assertForbidden();
    }

    public function test_dlq_read_token_can_read_dlq(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken(
            'dlq-reader',
            [\App\Enums\TokenAbility::DLQ_READ->value]
        )->plainTextToken;

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/dlq');

        $response->assertOk();
    }

    public function test_dlq_read_token_cannot_replay_dlq_event(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken(
            'dlq-reader',
            [\App\Enums\TokenAbility::DLQ_READ->value]
        )->plainTextToken;

        $response = $this
            ->withToken($token)
            ->postJson('/api/v1/dlq/999999/replay');

        $response->assertForbidden();
    }
}