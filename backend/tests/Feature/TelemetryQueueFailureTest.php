<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\NonRetryableTelemetryException;
use App\Jobs\ProcessTelemetryBatchJob;
use App\Services\TelemetryService;
use Illuminate\Contracts\Queue\Job as JobContract;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class TelemetryQueueFailureTest extends TestCase
{
    public function test_transient_queue_failure_is_propagated_for_retry(): void
    {
        $telemetryService = Mockery::mock(TelemetryService::class);

        $telemetryService
            ->shouldReceive('ingest')
            ->once()
            ->andThrow(
                new RuntimeException('Temporary telemetry processing failure.')
            );

        $queueJob = Mockery::mock(JobContract::class);

        $queueJob
            ->shouldReceive('fail')
            ->never();

        $job = new ProcessTelemetryBatchJob([
            [
                'event_id' => 'aa0e8400-e29b-41d4-a716-446655440000',
                'tenant_id' => 'aa0e8400-e29b-41d4-a716-446655440001',
                'source_id' => 'aa0e8400-e29b-41d4-a716-446655440002',
                'event_type' => 'telemetry.power',
                'timestamp' => now()->toISOString(),
                'schema_version' => 1,
            ],
        ]);

        $job->setJob($queueJob);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Temporary telemetry processing failure.'
        );

        $job->handle($telemetryService);
    }

    public function test_transient_queue_failure_preserves_retry_configuration(): void
    {
        $job = new ProcessTelemetryBatchJob([]);

        $this->assertSame(3, $job->tries);
        $this->assertSame(60, $job->timeout);
        $this->assertSame(10, $job->backoff);
    }

    public function test_non_retryable_queue_failure_is_failed_immediately(): void
    {
        $exception = new NonRetryableTelemetryException(
            'Invalid telemetry payload.'
        );

        $telemetryService = Mockery::mock(TelemetryService::class);

        $telemetryService
            ->shouldReceive('ingest')
            ->once()
            ->andThrow($exception);

        $queueJob = Mockery::mock(JobContract::class);

        $queueJob
            ->shouldReceive('fail')
            ->once()
            ->with($exception);

        $job = new ProcessTelemetryBatchJob([
            [
                'event_id' => 'bb0e8400-e29b-41d4-a716-446655440000',
                'tenant_id' => 'bb0e8400-e29b-41d4-a716-446655440001',
                'source_id' => 'bb0e8400-e29b-41d4-a716-446655440002',
                'event_type' => 'telemetry.power',
                'timestamp' => now()->toISOString(),
                'schema_version' => 1,
            ],
        ]);

        $job->setJob($queueJob);

        $job->handle($telemetryService);

        $this->assertTrue(true);
    }
}
