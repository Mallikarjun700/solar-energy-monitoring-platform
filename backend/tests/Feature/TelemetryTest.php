<?php

namespace Tests\Feature;


use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Jobs\ProcessTelemetryBatchJob;
use Illuminate\Support\Facades\Queue;

class TelemetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_telemetry_ingestion_dispatches_async_batch_job(): void
    {
        Queue::fake();

        $payload = [
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
                        'location' => 'Block A',
                    ],
                    'payload' => [
                        'power_kw' => 52.5,
                        'temperature' => 29.5,
                        'voltage' => 230,
                        'current' => 12.8,
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/telemetry/events', $payload);

        $response->assertStatus(202);

        Queue::assertPushed(
            ProcessTelemetryBatchJob::class,
            function (ProcessTelemetryBatchJob $job) {
                return count($job->events) === 1
                    && $job->events[0]['event_id'] === '550e8400-e29b-41d4-a716-446655440000';
            }
        );
    }
}

