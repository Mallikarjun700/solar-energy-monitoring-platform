<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorrelationIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_generates_correlation_id(): void
    {
        $response = $this->getJson('/api/v1/telemetry');

        $response->assertHeader('X-Correlation-ID');

        $this->assertNotEmpty(
            $response->headers->get('X-Correlation-ID')
        );
    }

    public function test_existing_correlation_id_is_preserved(): void
    {
        $correlationId = 'test-correlation-123';

        $response = $this
            ->withHeader('X-Correlation-ID', $correlationId)
            ->getJson('/api/v1/telemetry');

        $response->assertHeader(
            'X-Correlation-ID',
            $correlationId
        );
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

        $response->assertStatus(202);
        $response->assertHeader('X-Correlation-ID', $correlationId);

        \Illuminate\Support\Facades\Queue::assertPushed(
            \App\Jobs\ProcessTelemetryBatchJob::class,
            function (\App\Jobs\ProcessTelemetryBatchJob $job) use ($correlationId) {
                return $job->correlationId === $correlationId;
            }
        );
    }
}