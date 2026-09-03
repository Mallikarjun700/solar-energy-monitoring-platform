<?php

namespace App\Jobs;

use App\Models\AlertRule;
use App\Services\AlertCreationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateTelemetryAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 10;

    /**
     * @param  array<string, mixed>  $telemetry
     */
    public function __construct(
        public array $telemetry
    ) {}

    public function handle(
        AlertCreationService $alertCreationService
    ): void {
        $tenantId = $this->telemetry['tenant_id'] ?? null;

        if ($tenantId === null) {
            return;
        }

        $rules = AlertRule::query()
            ->where('tenant_id', $tenantId)
            ->where('enabled', true)
            ->get();

        foreach ($rules as $rule) {
            $alertCreationService->evaluateAndResolve(
                $this->telemetry,
                $rule
            );
        }
    }
}
