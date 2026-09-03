<?php

namespace Tests\Feature;

use App\Enums\AlertOperator;
use App\Enums\AlertSeverity;
use App\Models\AlertRule;
use App\Services\TelemetryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TelemetryAlertIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_telemetry_violation_creates_alert(): void
    {
        $tenantId = (string) Str::uuid();

        AlertRule::factory()->create([
            'tenant_id' => $tenantId,
            'metric' => 'temperature',
            'operator' => AlertOperator::GREATER_THAN,
            'threshold' => 80,
            'severity' => AlertSeverity::CRITICAL,
            'alert_type' => 'HIGH_TEMPERATURE',
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

        $this->assertDatabaseCount('alerts', 1);

        $this->assertDatabaseHas('alerts', [
            'tenant_id' => $tenantId,
            'device_id' => 100,
            'event_id' => $eventId,
            'alert_type' => 'HIGH_TEMPERATURE',
            'status' => 'open',
        ]);
    }

    public function test_normal_telemetry_does_not_create_alert(): void
    {
        $tenantId = (string) Str::uuid();

        AlertRule::factory()->create([
            'tenant_id' => $tenantId,
            'metric' => 'temperature',
            'operator' => AlertOperator::GREATER_THAN,
            'threshold' => 80,
        ]);

        app(TelemetryService::class)->ingest([
            [
                'event_id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'source_id' => (string) Str::uuid(),
                'event_type' => 'telemetry',
                'timestamp' => now()->toISOString(),
                'schema_version' => 1,
                'attributes' => [],
                'payload' => [
                    'device_id' => 100,
                    'temperature' => 70,
                ],
            ],
        ]);

        $this->assertDatabaseCount('alerts', 0);
    }

    public function test_repeated_violations_do_not_create_duplicate_active_alerts(): void
    {
        $tenantId = (string) Str::uuid();

        AlertRule::factory()->create([
            'tenant_id' => $tenantId,
            'metric' => 'temperature',
            'operator' => AlertOperator::GREATER_THAN,
            'threshold' => 80,
        ]);

        $service = app(TelemetryService::class);

        $service->ingest([
            [
                'event_id' => (string) Str::uuid(),
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

        $service->ingest([
            [
                'event_id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'source_id' => (string) Str::uuid(),
                'event_type' => 'telemetry',
                'timestamp' => now()->addMinute()->toISOString(),
                'schema_version' => 1,
                'attributes' => [],
                'payload' => [
                    'device_id' => 100,
                    'temperature' => 90,
                ],
            ],
        ]);

        $this->assertDatabaseCount('alerts', 1);
    }
}
