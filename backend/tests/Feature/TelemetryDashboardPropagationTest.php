<?php

namespace Tests\Feature;

use App\Enums\TokenAbility;
use App\Jobs\ProcessTelemetryBatchJob;
use App\Models\Asset;
use App\Models\Device;
use App\Models\Plant;
use App\Models\Telemetry;
use App\Models\User;
use App\Services\TelemetryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class TelemetryDashboardPropagationTest extends TestCase
{
    use RefreshDatabase;

    public function test_processed_telemetry_propagates_to_dashboard_kpis_and_device_values(): void
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

        $token = $user->createToken(
            'telemetry-dashboard-test',
            [TokenAbility::TELEMETRY_WRITE->value]
        )->plainTextToken;

        $payload = [
            'events' => [
                [
                    'event_id' => $eventId,
                    'tenant_id' => $tenantId,
                    'source_id' => $sourceId,
                    'event_type' => 'telemetry.power',
                    'timestamp' => now()->startOfHour()->toISOString(),
                    'schema_version' => 1,
                    'attributes' => [
                        'device_id' => $device->id,
                    ],
                    'payload' => [
                        'power_kw' => 52.5,
                        'energy_generated' => 12.75,
                        'temperature' => 29.5,
                        'voltage' => 230,
                        'current' => 12.8,
                    ],
                ],
            ],
        ];

        // 1. Ingest telemetry through the API.
        $response = $this
            ->withToken($token)
            ->withHeader('Idempotency-Key', 'telemetry-dashboard-001')
            ->postJson('/api/v1/telemetry/events', $payload);

        $response
            ->assertStatus(202)
            ->assertJson([
                'accepted' => 1,
                'jobs_dispatched' => 1,
            ]);

        // 2. Execute the queued telemetry processor.
        $job = null;

        Queue::assertPushed(
            ProcessTelemetryBatchJob::class,
            function (ProcessTelemetryBatchJob $queuedJob) use (&$job): bool {
                $job = $queuedJob;

                return true;
            }
        );

        $this->assertNotNull($job);

        $job->handle(app(TelemetryService::class));

        // 3. Verify the raw telemetry event was persisted.
        $this->assertDatabaseHas('telemetry_events', [
            'event_id' => $eventId,
            'tenant_id' => $tenantId,
        ], 'pgsql_telemetry');

        // 4. Verify the processed MySQL telemetry projection exists.
        $telemetry = Telemetry::query()
            ->where('tenant_id', $tenantId)
            ->where('device_id', $device->id)
            ->first();

        $this->assertNotNull($telemetry);
        $this->assertSame(52.5, (float) $telemetry->power);
        $this->assertSame(12.75, (float) $telemetry->energy_generated);
        $this->assertSame(29.5, (float) $telemetry->temperature);
        $this->assertSame(230.0, (float) $telemetry->voltage);
        $this->assertSame(12.8, (float) $telemetry->current);

        // 5. Read the dashboard through the actual API.
        $dashboardResponse = $this
            ->withToken($token)
            ->getJson("/api/v1/dashboard?tenant_id={$tenantId}");

        $dashboardResponse->assertOk();

        $dashboard = $dashboardResponse->json('data');

        // 6. Verify the telemetry propagated into dashboard KPIs.
        $this->assertSame(
            52.5,
            (float) $dashboard['kpis']['currentPowerKw']
        );

        $this->assertSame(
            12.75,
            (float) $dashboard['kpis']['todayEnergyKwh']
        );

        // 7. Verify the same telemetry propagated into the device view.
        $dashboardDevice = collect($dashboard['devices'])
            ->firstWhere('id', $device->id);

        $this->assertNotNull($dashboardDevice);

        $this->assertSame(52.5, (float) $dashboardDevice['currentPowerKw']);
        $this->assertSame(29.5, (float) $dashboardDevice['temperature']);
        $this->assertSame(230.0, (float) $dashboardDevice['voltage']);
        $this->assertSame(12.8, (float) $dashboardDevice['current']);
        $this->assertNotNull($dashboardDevice['telemetryTimestamp']);
    }
}
