<?php

namespace Tests\Feature;

use App\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorrelationIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_generates_correlation_id(): void
    {
        $response = $this->getJson('/api/v1/plants');

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
        $correlationId = 'test-correlation-123';

        $response = $this
            ->withHeader('X-Correlation-ID', $correlationId)
            ->getJson('/api/v1/plants');

        $response->assertHeader(
            'X-Correlation-ID',
            $correlationId
        );
        $response->assertJsonPath('correlation_id', $correlationId);
    }

    public function test_correlation_id_is_propagated_to_telemetry_job(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $correlationId = 'test-correlation-123';

        $payload = [
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
                        'temperature' => 25.5,
                    ],
                ],
            ],
        ];

        $response = $this
            ->withHeader('X-Correlation-ID', $correlationId)
            ->postJson('/api/v1/telemetry/events', $payload);

        $response
            ->assertStatus(202)
            ->assertHeader('X-Correlation-ID', $correlationId)
            ->assertJsonPath('accepted', 1)
            ->assertJsonPath('jobs_dispatched', 1)
            ->assertJsonPath('correlation_id', $correlationId);

        \Illuminate\Support\Facades\Queue::assertPushed(
            \App\Jobs\ProcessTelemetryBatchJob::class,
            function (\App\Jobs\ProcessTelemetryBatchJob $job) use ($correlationId) {
                return $job->correlationId === $correlationId;
            }
        );
    }

    public function test_no_content_response_does_not_receive_a_json_body(): void
    {
        $asset = Asset::factory()->create();

        $response = $this->deleteJson("/api/v1/assets/{$asset->id}");

        $response
            ->assertNoContent()
            ->assertHeader('X-Correlation-ID')
            ->assertContent('');
    }
}
