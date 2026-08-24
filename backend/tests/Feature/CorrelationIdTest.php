<?php

namespace Tests\Feature;

use App\Enums\TokenAbility;
use App\Jobs\ProcessTelemetryBatchJob;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class CorrelationIdTest extends TestCase
{
    use RefreshDatabase;

    private function token(array $abilities = []): string
    {
        $user = User::factory()->create();

        return $user->createToken(
            'correlation-test',
            $abilities
        )->plainTextToken;
    }

    public function test_request_generates_correlation_id(): void
    {
        $token = $this->token();

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/plants');

        $response->assertHeader('X-Correlation-ID');

        $correlationId = $response->headers->get('X-Correlation-ID');

        $this->assertNotEmpty($correlationId);

        $response
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('correlation_id', $correlationId);
    }

    public function test_existing_correlation_id_is_preserved(): void
    {
        $token = $this->token();

        $correlationId = 'test-correlation-123';

        $response = $this
            ->withToken($token)
            ->withHeader('X-Correlation-ID', $correlationId)
            ->getJson('/api/v1/plants');

        $response->assertHeader(
            'X-Correlation-ID',
            $correlationId
        );

        $response->assertJsonPath(
            'correlation_id',
            $correlationId
        );
    }

    public function test_correlation_id_is_propagated_to_telemetry_job(): void
    {
        Queue::fake();

        $token = $this->token([
            TokenAbility::TELEMETRY_WRITE->value,
        ]);

        $correlationId = 'test-correlation-123';

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
                        'temperature' => 25.5,
                    ],
                ],
            ],
        ];

        $response = $this
            ->withToken($token)
            ->withHeader('X-Correlation-ID', $correlationId)
            ->withHeader('Idempotency-Key', 'correlation-test-001')
            ->postJson('/api/v1/telemetry/events', $payload);

        $response
            ->assertStatus(202)
            ->assertHeader('X-Correlation-ID', $correlationId)
            ->assertJsonPath('accepted', 1)
            ->assertJsonPath('jobs_dispatched', 1)
            ->assertJsonPath('correlation_id', $correlationId);

        Queue::assertPushed(
            ProcessTelemetryBatchJob::class,
            function (ProcessTelemetryBatchJob $job) use ($correlationId) {
                return $job->correlationId === $correlationId;
            }
        );
    }

    public function test_no_content_response_does_not_receive_a_json_body(): void
    {
        $token = $this->token();

        $asset = Asset::factory()->create();

        $response = $this
            ->withToken($token)
            ->deleteJson("/api/v1/assets/{$asset->id}");

        $response
            ->assertNoContent()
            ->assertHeader('X-Correlation-ID')
            ->assertContent('');
    }
}