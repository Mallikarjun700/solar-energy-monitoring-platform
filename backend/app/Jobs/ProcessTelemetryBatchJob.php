<?php

namespace App\Jobs;

use App\Exceptions\NonRetryableTelemetryException;
use App\Services\TelemetryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

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

        try {
            if (
                app()->environment('testing')
                && ($this->events[0]['force_failure'] ?? false)
            ) {
                throw new \RuntimeException(
                    'Intentional telemetry queue failure.'
                );
            }

            $telemetryService->ingest($this->events);
        } catch (Throwable $exception) {
            if ($this->isNonRetryable($exception)) {
                $this->fail($exception);

                return;
            }

            throw $exception;
        }
    }

    /**
     * Determine whether the exception should bypass retries.
     */
    private function isNonRetryable(Throwable $exception): bool
    {
        return $exception instanceof NonRetryableTelemetryException
            || $exception instanceof \InvalidArgumentException;
    }

    public function attempts(): int
    {
        return $this->job?->attempts() ?? 1;
    }
}
