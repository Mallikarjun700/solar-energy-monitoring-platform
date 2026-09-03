<?php

namespace Tests\Feature;

use App\Enums\TokenAbility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelemetryRequestSizeTest extends TestCase
{
    use RefreshDatabase;

    private function telemetryToken(): string
    {
        $user = User::factory()->create();

        return $user->createToken(
            'telemetry-size-test',
            [TokenAbility::TELEMETRY_WRITE->value]
        )->plainTextToken;
    }

    public function test_telemetry_request_within_size_limit_is_accepted(): void
    {
        $payload = [
            'events' => [
                [
                    'event_id' => '550e8400-e29b-41d4-a716-446655440000',
                    'tenant_id' => '550e8400-e29b-41d4-a716-446655440001',
                    'source_id' => '550e8400-e29b-41d4-a716-446655440002',
                    'event_type' => 'telemetry.power',
                    'timestamp' => now()->toISOString(),
                    'schema_version' => 1,
                    'attributes' => [],
                    'payload' => [
                        'device_id' => 1,
                        'power_kw' => 52.5,
                    ],
                ],
            ],
        ];

        $response = $this->withToken($this->telemetryToken())->withHeader('Idempotency-Key', 'request-size-valid')
            ->postJson('/api/v1/telemetry/events', $payload);
        $response->assertStatus(202);
    }

    public function test_telemetry_request_over_size_limit_is_rejected(): void
    {
        $response = $this->withToken($this->telemetryToken())->withHeader('Idempotency-Key', 'request-size-too-large')
            ->withHeader('Content-Length', (string) (6 * 1024 * 1024))->postJson('/api/v1/telemetry/events', [
            'events' => [],
        ]);
        $response->assertStatus(422);
    }
}
