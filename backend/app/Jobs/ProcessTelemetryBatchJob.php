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

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $events
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(TelemetryService $telemetryService): void
    {
        $telemetryService->ingest($this->events);
    }
}
