<?php

namespace App\Services;

class QueueMetricsService
{
    /**
     * Store queue job start times.
     *
     * @var array<string, float>
     */
    private array $startTimes = [];

    public function start(string $jobId): void
    {
        $this->startTimes[$jobId] = microtime(true);
    }

    public function finish(string $jobId): ?float
    {
        if (! isset($this->startTimes[$jobId])) {
            return null;
        }

        $durationMs = (microtime(true) - $this->startTimes[$jobId]) * 1000;

        unset($this->startTimes[$jobId]);

        return round($durationMs, 2);
    }
}
