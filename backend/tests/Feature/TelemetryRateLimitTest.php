<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TelemetryRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_telemetry_rate_limit_returns_429_when_exceeded(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, [
            \App\Enums\TokenAbility::TELEMETRY_WRITE->value,
        ]);

        $key = 'user:' . $user->getAuthIdentifier();

        RateLimiter::clear($key);

        $limit = (int) config(
            'telemetry.rate_limit.requests_per_minute',
            60
        );

        RateLimiter::hit($key, 60);

        $response = $this->postJson(
            '/api/v1/telemetry/events',
            [
                'events' => [
                    [
                        'event_id' => (string) \Illuminate\Support\Str::uuid(),
                        'tenant_id' => (string) \Illuminate\Support\Str::uuid(),
                        'source_id' => (string) \Illuminate\Support\Str::uuid(),
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
            ]
        );

        $response->assertStatus(429);

        $this->assertNotEmpty(
            $response->headers->get('Retry-After')
        );
    }
}