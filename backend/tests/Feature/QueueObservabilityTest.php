<?php

namespace Tests\Feature;

use App\Jobs\ProcessTelemetryBatchJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueObservabilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_queue_job_contains_correlation_id(): void
    {
        Queue::fake();

        $correlationId = 'test-correlation-123';

        $events = [[
            'event_id' => 'evt-observability-001',
            'tenant_id' => 'tenant-001',
            'source_id' => 'source-001',
            'event_type' => 'telemetry.power',
            'timestamp' => now()->toISOString(),
            'schema_version' => 1,
            'attributes' => [
                'device_id' => 101,
            ],
            'payload' => [
                'power_kw' => 50,
            ],
            'correlation_id' => $correlationId,
        ]];

        ProcessTelemetryBatchJob::dispatch($events);

        Queue::assertPushed(
            ProcessTelemetryBatchJob::class,
            function (ProcessTelemetryBatchJob $job) use ($correlationId) {
                return $job->events[0]['correlation_id'] === $correlationId;
            }
        );
    }
}
