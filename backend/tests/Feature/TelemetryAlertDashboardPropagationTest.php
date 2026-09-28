<?php

namespace Tests\Feature;

use App\Enums\AlertOperator;
use App\Enums\AlertSeverity;
use App\Enums\TokenAbility;
use App\Jobs\EvaluateTelemetryAlertsJob;
use App\Jobs\ProcessTelemetryBatchJob;
use App\Models\Alert;
use App\Models\AlertRule;
use App\Models\Asset;
use App\Models\Device;
use App\Models\Plant;
use App\Models\User;
use App\Services\AlertCreationService;
use App\Services\TelemetryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class TelemetryAlertDashboardPropagationTest extends TestCase
{
    use RefreshDatabase;

    public function test_triggered_telemetry_generates_alert_and_propagates_to_dashboard(): void
    {
        Queue::fake();

        $tenantId = (string) Str::uuid();
        $sourceId = (string) Str::uuid();
        $eventId = (string) Str::uuid();

        $user = User::factory()
            ->forTenant($tenantId)
            ->create();

        $plant = Plant::factory()->create([
            'tenant_id' => $tenantId,
        ]);

        $asset = Asset::factory()->create([
            'tenant_id' => $tenantId,
            'plant_id' => $plant->id,
        ]);

        $device = Device::factory()->create([
            'tenant_id' => $tenantId,
            'asset_id' => $asset->id,
        ]);

        $rule = AlertRule::factory()->create([
            'tenant_id' => $tenantId,
            'name' => 'High Temperature',
            'metric' => 'temperature',
            'operator' => AlertOperator::GREATER_THAN,
            'threshold' => 80,
            'severity' => AlertSeverity::CRITICAL,
            'alert_type' => 'HIGH_TEMPERATURE',
            'enabled' => true,
        ]);

        $token = $user->createToken(
            'telemetry-alert-dashboard-test',
            [TokenAbility::TELEMETRY_WRITE->value]
        )->plainTextToken;

        $payload = [
            'events' => [
                [
                    'event_id' => $eventId,
                    'tenant_id' => $tenantId,
                    'source_id' => $sourceId,
                    'event_type' => 'telemetry.temperature',
                    'timestamp' => now()->startOfHour()->toISOString(),
                    'schema_version' => 1,
                    'attributes' => [
                        'device_id' => $device->id,
                    ],
                    'payload' => [
                        'temperature' => 95,
                        'power_kw' => 52.5,
                        'energy_generated' => 12.75,
                        'voltage' => 230,
                        'current' => 12.8,
                    ],
                ],
            ],
        ];

        // 1. Telemetry enters through the API.
        $response = $this
            ->withToken($token)
            ->withHeader('Idempotency-Key', 'telemetry-alert-dashboard-001')
            ->postJson('/api/v1/telemetry/events', $payload);

        $response
            ->assertStatus(202)
            ->assertJson([
                'accepted' => 1,
                'jobs_dispatched' => 1,
            ]);

        // 2. Execute telemetry processing.
        $processingJob = null;

        Queue::assertPushed(
            ProcessTelemetryBatchJob::class,
            function (ProcessTelemetryBatchJob $job) use (&$processingJob): bool {
                $processingJob = $job;

                return true;
            }
        );

        $this->assertNotNull($processingJob);

        $processingJob->handle(
            app(TelemetryService::class)
        );

        // 3. Telemetry processing should dispatch alert evaluation.
        $alertJob = null;

        Queue::assertPushed(
            EvaluateTelemetryAlertsJob::class,
            function (EvaluateTelemetryAlertsJob $job) use (&$alertJob): bool {
                $alertJob = $job;

                return true;
            }
        );

        $this->assertNotNull($alertJob);

        // 4. Execute alert evaluation.
        $alertJob->handle(
            app(AlertCreationService::class)
        );

        // 5. Verify the alert was generated for the correct tenant/device/rule.
        $alert = Alert::query()
            ->where('tenant_id', $tenantId)
            ->where('device_id', $device->id)
            ->where('rule_id', $rule->id)
            ->where('event_id', $eventId)
            ->first();

        $this->assertNotNull($alert);
        $this->assertSame('HIGH_TEMPERATURE', $alert->alert_type->value ?? $alert->alert_type);
        $this->assertSame('critical', $alert->severity->value ?? $alert->severity);
        $this->assertSame('open', $alert->status->value ?? $alert->status);
        $this->assertStringContainsString('temperature=95', $alert->message);

        // 6. Verify the dashboard exposes the generated alert.
        $dashboardResponse = $this
            ->withToken($token)
            ->getJson("/api/v1/dashboard?tenant_id={$tenantId}");

        $dashboardResponse->dump();
        $dashboardResponse->assertOk();

        $dashboardAlerts = $dashboardResponse->json('data.alerts');

        $this->assertCount(1, $dashboardAlerts);

        $dashboardAlert = $dashboardAlerts[0];

        $this->assertSame($alert->id, $dashboardAlert['id']);
        $this->assertSame($tenantId, $dashboardAlert['tenant_id']);
        $this->assertSame($device->id, $dashboardAlert['device_id']);
        $this->assertSame($rule->id, $dashboardAlert['rule_id']);
        $this->assertSame('HIGH_TEMPERATURE', $dashboardAlert['alert_type']);
        $this->assertSame('critical', $dashboardAlert['severity']);
        $this->assertSame('open', $dashboardAlert['status']);
        $this->assertSame($eventId, $alert->event_id);
    }
}
