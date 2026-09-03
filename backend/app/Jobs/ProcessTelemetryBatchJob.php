<?php

namespace App\Jobs;

use App\Services\TelemetryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessTelemetryBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $events,
        public ?string $correlationId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TelemetryService $telemetryService): void
    {
        $this->startedAt = microtime(true);
        if ($this->correlationId) {
            app()->instance('correlation_id', $this->correlationId);
        }

        if (app()->environment('testing') && ($this->events[0]['force_failure'] ?? false)) {
            throw new \RuntimeException(
                'Intentional telemetry queue failure.'
            );
        }
        $telemetryService->ingest($this->events);
    }

    public function attempts(): int
    {
        return $this->job?->attempts() ?? 1;
    }
}
