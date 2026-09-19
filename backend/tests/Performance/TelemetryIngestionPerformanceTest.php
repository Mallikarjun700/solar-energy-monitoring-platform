<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Services\TelemetryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class TelemetryIngestionPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_250_event_ingestion_completes_within_performance_budget(): void
    {
        Queue::fake();

        $events = $this->makeEvents(250);
        $service = app(TelemetryService::class);

        $startedAt = microtime(true);

        $result = $service->ingest($events);

        $durationMs = (microtime(true) - $startedAt) * 1000;

        $this->assertSame(250, $result['accepted']);
        $this->assertSame(0, $result['duplicates']);
        $this->assertSame(0, $result['rejected']);

        $this->assertLessThan(
            5000,
            $durationMs,
            sprintf(
                '250-event telemetry ingestion exceeded the 5 second performance budget: %.2f ms',
                $durationMs
            )
        );

        fwrite(
            STDOUT,
            sprintf(
                PHP_EOL.'Telemetry performance: 250 events = %.2f ms'.PHP_EOL,
                $durationMs
            )
        );
    }

    public function test_1000_event_ingestion_completes_within_performance_budget(): void
    {
        Queue::fake();

        $events = $this->makeEvents(1000);
        $service = app(TelemetryService::class);

        $startedAt = microtime(true);

        $result = $service->ingest($events);

        $durationMs = (microtime(true) - $startedAt) * 1000;

        $this->assertSame(1000, $result['accepted']);
        $this->assertSame(0, $result['duplicates']);
        $this->assertSame(0, $result['rejected']);

        $this->assertLessThan(
            10000,
            $durationMs,
            sprintf(
                '1000-event telemetry ingestion exceeded the 10 second performance budget: %.2f ms',
                $durationMs
            )
        );

        fwrite(
            STDOUT,
            sprintf(
                PHP_EOL.'Telemetry performance: 1000 events = %.2f ms'.PHP_EOL,
                $durationMs
            )
        );
    }

    public function test_1000_duplicate_events_are_processed_within_performance_budget(): void
    {
        Queue::fake();

        $events = $this->makeEvents(1000);
        $service = app(TelemetryService::class);

        $firstResult = $service->ingest($events);

        $this->assertSame(1000, $firstResult['accepted']);
        $this->assertSame(0, $firstResult['duplicates']);

        Queue::fake();

        $startedAt = microtime(true);

        $duplicateResult = $service->ingest($events);

        $durationMs = (microtime(true) - $startedAt) * 1000;

        $this->assertSame(0, $duplicateResult['accepted']);
        $this->assertSame(1000, $duplicateResult['duplicates']);
        $this->assertSame(0, $duplicateResult['rejected']);

        $this->assertLessThan(
            5000,
            $durationMs,
            sprintf(
                '1000 duplicate telemetry events exceeded the 5 second performance budget: %.2f ms',
                $durationMs
            )
        );

        fwrite(
            STDOUT,
            sprintf(
                PHP_EOL.'Telemetry duplicate performance: 1000 events = %.2f ms'.PHP_EOL,
                $durationMs
            )
        );
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
