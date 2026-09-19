<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Jobs\ProcessTelemetryBatchJob;
use App\Services\TelemetryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class TelemetryQueuePerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_250_event_queue_job_completes_within_performance_budget(): void
    {
        Queue::fake();

        $events = $this->makeEvents(250);
        $job = new ProcessTelemetryBatchJob($events);

        $startedAt = microtime(true);

        $job->handle(app(TelemetryService::class));

        $durationMs = (microtime(true) - $startedAt) * 1000;

        $this->assertLessThan(
            5000,
            $durationMs,
            sprintf(
                '250-event queue job exceeded the 5 second performance budget: %.2f ms',
                $durationMs
            )
        );

        fwrite(
            STDOUT,
            sprintf(
                PHP_EOL.'Queue performance: 250 events = %.2f ms'.PHP_EOL,
                $durationMs
            )
        );
    }

    public function test_1000_event_queue_job_completes_within_performance_budget(): void
    {
        Queue::fake();

        $events = $this->makeEvents(1000);
        $job = new ProcessTelemetryBatchJob($events);

        $startedAt = microtime(true);

        $job->handle(app(TelemetryService::class));

        $durationMs = (microtime(true) - $startedAt) * 1000;

        $this->assertLessThan(
            10000,
            $durationMs,
            sprintf(
                '1000-event queue job exceeded the 10 second performance budget: %.2f ms',
                $durationMs
            )
        );

        fwrite(
            STDOUT,
            sprintf(
                PHP_EOL.'Queue performance: 1000 events = %.2f ms'.PHP_EOL,
                $durationMs
            )
        );
    }

    public function test_queue_job_preserves_batch_size_and_retry_configuration(): void
    {
        $events = $this->makeEvents(250);
        $job = new ProcessTelemetryBatchJob($events);

        $this->assertCount(250, $job->events);
        $this->assertSame(3, $job->tries);
        $this->assertSame(60, $job->timeout);
        $this->assertSame(10, $job->backoff);
    }

    private function makeEvents(int $count): array
    {
        $events = [];

        for ($i = 0; $i < $count; $i++) {
            $events[] = [
                'event_id' => Str::uuid()->toString(),
                'tenant_id' => Str::uuid()->toString(),
                'source_id' => Str::uuid()->toString(),
                'event_type' => 'telemetry.power',
                'timestamp' => now()->toISOString(),
                'schema_version' => 1,
                'attributes' => [
                    'device_id' => 1,
                    'location' => 'Block A',
                ],
                'payload' => [
                    'power_kw' => 125.5,
                    'temperature' => 42.3,
                    'voltage' => 415,
                    'current' => 180,
                ],
            ];
        }

        return $events;
    }
}
