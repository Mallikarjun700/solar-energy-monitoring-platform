<?php

namespace Tests\Feature;

use App\Services\QueueMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueMetricsLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_metrics_service_is_registered(): void
    {
        $service = app(QueueMetricsService::class);
        $this->assertInstanceOf(QueueMetricsService::class, $service);
    }

    public function test_queue_metrics_service_tracks_duration(): void
    {
        $service = app(QueueMetricsService::class);

        $service->start('job-123');

        usleep(5000);

        $duration = $service->finish('job-123');

        $this->assertNotNull($duration);
        $this->assertGreaterThanOrEqual(5, $duration);
    }

    public function test_queue_metrics_service_returns_null_for_unknown_job(): void
    {
        $service = app(QueueMetricsService::class);

        $duration = $service->finish('unknown-job-xyz');

        $this->assertNull($duration);
    }

    public function test_app_service_provider_registers_metrics_service_singleton(): void
    {
        $service1 = app(QueueMetricsService::class);
        $service2 = app(QueueMetricsService::class);

        $this->assertSame($service1, $service2);
    }
}
