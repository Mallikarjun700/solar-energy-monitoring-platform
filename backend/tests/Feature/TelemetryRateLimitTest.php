<?php

namespace Tests\Feature;

use App\Enums\TokenAbility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TelemetryRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_telemetry_rate_limit_returns_429_when_exceeded(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, [
            TokenAbility::TELEMETRY_WRITE->value,
        ]);

        Queue::fake();
        config(['telemetry.rate_limit.requests_per_minute' => 1]);

        $payload = [
            'events' => [
                [
                    'event_id' => (string) Str::uuid(),
                    'tenant_id' => (string) Str::uuid(),
                    'source_id' => (string) Str::uuid(),
                    'event_type' => 'telemetry.power',
                    'timestamp' => now()->toISOString(),
                    'schema_version' => 1,
                    'attributes' => [
                        'device_id' => 1,
                    ],
                    'payload' => [
                        'power_kw' => 10,
                    ],
                ],
            ],
        ];

        $this
            ->withHeader('Idempotency-Key', 'telemetry-rate-limit-001')
            ->postJson(
                '/api/v1/telemetry/events',
                $payload
            );

        $response = $this
            ->withHeader('Idempotency-Key', 'telemetry-rate-limit-002')
            ->postJson(
                '/api/v1/telemetry/events',
                $payload
            );

        $response->assertStatus(429);

        $this->assertNotEmpty(
            $response->headers->get('Retry-After')
        );
    }
}
