<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessTelemetryBatchJob;
use App\Services\TelemetryService;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Database\QueryException;
use Mockery;
use Tests\TestCase;

class TelemetryDatabaseFailureTest extends TestCase
{
    public function test_database_failure_is_propagated_as_retryable_exception(): void
    {
        $databaseException = new QueryException(
            'pgsql_telemetry',
            'insert into telemetry_events',
            [],
            new \RuntimeException('Telemetry database connection failed.')
        );

        $telemetryService = Mockery::mock(TelemetryService::class);

        $telemetryService
            ->shouldReceive('ingest')
            ->once()
            ->andThrow($databaseException);

        $job = new ProcessTelemetryBatchJob([
            [
                'event_id' => '990e8400-e29b-41d4-a716-446655440000',
                'tenant_id' => '990e8400-e29b-41d4-a716-446655440001',
                'source_id' => '990e8400-e29b-41d4-a716-446655440002',
                'event_type' => 'telemetry.power',
                'timestamp' => now()->toISOString(),
                'schema_version' => 1,
            ],
        ]);

        $queueJob = Mockery::mock(Job::class);

        $queueJob
            ->shouldReceive('fail')
            ->never();

        $job->setJob($queueJob);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Telemetry database connection failed.');

        $job->handle($telemetryService);
    }

    public function test_database_failure_does_not_bypass_retry_configuration(): void
    {
        $job = new ProcessTelemetryBatchJob([]);

        $this->assertSame(3, $job->tries);
        $this->assertSame(60, $job->timeout);
        $this->assertSame(10, $job->backoff);
    }
}
