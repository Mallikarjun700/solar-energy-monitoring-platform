<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\QueueMetricsService;

class QueueMetricsServiceTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_it_records_and_returns_job_duration(): void
    {
        $service = new QueueMetricsService();

        $service->start('job-123');

        usleep(1000);

        $duration = $service->finish('job-123');

        $this->assertNotNull($duration);
        $this->assertGreaterThanOrEqual(1, $duration);
    }

    public function test_unknown_job_returns_null(): void
    {
        $service = new QueueMetricsService();

        $this->assertNull(
            $service->finish('unknown-job')
        );
    }
}
