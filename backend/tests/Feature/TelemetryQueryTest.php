<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Enums\TokenAbility;

class TelemetryQueryTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_valid_telemetry_filters_are_accepted(): void
    {
        $response = $this
            ->withToken($this->telemetryToken())
            ->getJson('/api/v1/telemetry/events?' . http_build_query([
                'tenant_id' => '550e8400-e29b-41d4-a716-446655440001',
                'source_id' => '550e8400-e29b-41d4-a716-446655440002',
                'event_type' => 'telemetry.power',
                'from' => '2026-01-01T00:00:00Z',
                'to' => '2026-01-31T23:59:59Z',
                'per_page' => 50,
            ]));

        $response->assertSuccessful();
    }

    public function test_invalid_per_page_is_rejected(): void
    {
        $response = $this
        ->withToken($this->telemetryToken())
        ->getJson(
            '/api/v1/telemetry/events?per_page=101'
        );

        $response->assertStatus(422);
    }

    public function test_invalid_date_range_is_rejected(): void
    {
        $response = $this
        ->withToken($this->telemetryToken())
        ->getJson('/api/v1/telemetry/events?' . http_build_query([
            'from' => '2026-02-01',
            'to' => '2026-01-01',
        ]));

        $response->assertStatus(422);
    }

    private function telemetryToken(): string
    {
        $user = User::factory()->create();

        return $user->createToken(
            'telemetry-test',
            [TokenAbility::TELEMETRY_WRITE->value]
        )->plainTextToken;
    }
}
