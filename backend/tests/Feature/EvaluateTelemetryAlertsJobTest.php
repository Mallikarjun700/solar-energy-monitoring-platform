<?php

namespace Tests\Feature;

use App\Enums\AlertOperator;
use App\Jobs\EvaluateTelemetryAlertsJob;
use App\Models\AlertRule;
use App\Services\TelemetryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class EvaluateTelemetryAlertsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_telemetry_ingestion_dispatches_alert_evaluation_job(): void
    {
        Queue::fake();

        $tenantId = (string) Str::uuid();

        AlertRule::factory()->create([
            'tenant_id' => $tenantId,
            'metric' => 'temperature',
            'operator' => AlertOperator::GREATER_THAN,
            'threshold' => 80,
            'enabled' => true,
        ]);

        $eventId = (string) Str::uuid();

        app(TelemetryService::class)->ingest([
            [
                'event_id' => $eventId,
                'tenant_id' => $tenantId,
                'source_id' => (string) Str::uuid(),
                'event_type' => 'telemetry',
                'timestamp' => now()->toISOString(),
                'schema_version' => 1,
                'attributes' => [],
                'payload' => [
                    'device_id' => 100,
                    'temperature' => 85,
                ],
            ],
        ]);

        Queue::assertPushed(
            EvaluateTelemetryAlertsJob::class,
            function (EvaluateTelemetryAlertsJob $job) use ($eventId) {
                return $job->telemetry['event_id'] === $eventId
                    && $job->telemetry['payload']['temperature'] === 85;
            }
        );
    }
}
