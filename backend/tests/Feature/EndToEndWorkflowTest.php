<?php

namespace Tests\Feature;

use App\Enums\AlertOperator;
use App\Enums\AlertSeverity;
use App\Enums\DeadLetterStatus;
use App\Enums\TokenAbility;
use App\Enums\UserRole;
use App\Models\AlertRule;
use App\Models\Asset;
use App\Models\DeadLetterEvent;
use App\Models\Device;
use App\Models\Plant;
use App\Models\TelemetryEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EndToEndWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_complete_asset_hierarchy(): void
    {
        $tenantId = (string) Str::uuid();

        $user = User::factory()
            ->forTenant($tenantId)
            ->create([
                'role' => UserRole::OPERATOR->value,
            ]);

        Sanctum::actingAs($user, [
            TokenAbility::PLANTS_READ->value,
            TokenAbility::PLANTS_WRITE->value,
            TokenAbility::ASSETS_READ->value,
            TokenAbility::ASSETS_WRITE->value,
            TokenAbility::DEVICES_READ->value,
            TokenAbility::DEVICES_WRITE->value,
        ]);

        $plantResponse = $this->postJson('/api/v1/plants', [
            'name' => 'E2E Solar Plant',
            'code' => 'E2E-PLANT-001',
            'location' => 'Bengaluru',
            'capacity_kw' => 5000,
            'status' => 'ACTIVE',
        ]);

        $plantResponse
            ->assertCreated()
            ->assertJsonPath('data.name', 'E2E Solar Plant');

        $plantId = $plantResponse->json('data.id');

        $this->assertDatabaseHas('plants', [
            'id' => $plantId,
            'tenant_id' => $tenantId,
            'name' => 'E2E Solar Plant',
        ]);

        $assetResponse = $this->postJson('/api/v1/assets', [
            'plant_id' => $plantId,
            'name' => 'E2E Inverter',
            'asset_type' => 'INVERTER',
            'serial_number' => 'E2E-INV-001',
            'status' => 'ACTIVE',
            'location' => 'Block A',
        ]);

        $assetResponse
            ->assertCreated()
            ->assertJsonPath('data.plant_id', $plantId);

        $assetId = $assetResponse->json('data.id');

        $this->assertDatabaseHas('assets', [
            'id' => $assetId,
            'plant_id' => $plantId,
            'tenant_id' => $tenantId,
        ]);

        $deviceResponse = $this->postJson('/api/v1/devices', [
            'asset_id' => $assetId,
            'device_type' => 'SMART_METER',
            'serial_number' => 'E2E-DEVICE-001',
            'status' => 'ONLINE',
        ]);

        $deviceResponse
            ->assertCreated()
            ->assertJsonPath('data.asset_id', $assetId);

        $deviceId = $deviceResponse->json('data.id');

        $this->assertDatabaseHas('devices', [
            'id' => $deviceId,
            'asset_id' => $assetId,
            'tenant_id' => $tenantId,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'tenant_id' => $tenantId,
        ]);
    }

    public function test_telemetry_ingestion_flows_into_persistence_and_alert_generation(): void
    {
        $tenantId = (string) Str::uuid();

        $user = User::factory()
            ->forTenant($tenantId)
            ->create([
                'role' => UserRole::OPERATOR->value,
            ]);

        Sanctum::actingAs($user, [
            TokenAbility::TELEMETRY_WRITE->value,
            TokenAbility::TELEMETRY_READ->value,
            TokenAbility::ALERTS_READ->value,
        ]);

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

        $response = $this
            ->withHeader('Idempotency-Key', 'e2e-telemetry-'.$eventId)
            ->postJson('/api/v1/telemetry/events', [
                'events' => [
                    [
                        'event_id' => $eventId,
                        'tenant_id' => $tenantId,
                        'source_id' => (string) Str::uuid(),
                        'event_type' => 'telemetry',
                        'timestamp' => now()->toISOString(),
                        'schema_version' => 1,
                        'attributes' => [
                            'device_id' => $device->id,
                            'location' => 'Block A',
                        ],
                        'payload' => [
                            'device_id' => $device->id,
                            'temperature' => 85,
                            'power_kw' => 125.5,
                            'voltage' => 230,
                            'current' => 12.8,
                        ],
                    ],
                ],
            ]);

        $response->assertStatus(202);

        $this->assertTrue(
            TelemetryEvent::query()
                ->where('event_id', $eventId)
                ->where('tenant_id', $tenantId)
                ->exists()
        );

        $this->assertDatabaseHas('alerts', [
            'tenant_id' => $tenantId,
            'device_id' => $device->id,
            'event_id' => $eventId,
            'alert_type' => 'HIGH_TEMPERATURE',
            'status' => 'open',
        ]);

        $alertResponse = $this->getJson(
            '/api/v1/alerts?tenant_id='.urlencode($tenantId)
        );

        $alertResponse
            ->assertOk()
            ->assertJsonFragment([
                'event_id' => $eventId,
                'alert_type' => 'HIGH_TEMPERATURE',
            ]);
    }

    public function test_tenant_isolation_is_preserved_across_complete_asset_hierarchy(): void
    {
        $tenantA = (string) Str::uuid();
        $tenantB = (string) Str::uuid();

        $userA = User::factory()
            ->forTenant($tenantA)
            ->create([
                'role' => UserRole::OPERATOR->value,
            ]);

        Sanctum::actingAs($userA, [
            TokenAbility::PLANTS_READ->value,
            TokenAbility::PLANTS_WRITE->value,
            TokenAbility::ASSETS_READ->value,
            TokenAbility::ASSETS_WRITE->value,
            TokenAbility::DEVICES_READ->value,
            TokenAbility::DEVICES_WRITE->value,
        ]);

        $plantA = Plant::factory()->create([
            'tenant_id' => $tenantA,
        ]);

        $assetA = Asset::factory()->create([
            'tenant_id' => $tenantA,
            'plant_id' => $plantA->id,
        ]);

        $deviceA = Device::factory()->create([
            'tenant_id' => $tenantA,
            'asset_id' => $assetA->id,
        ]);

        $plantB = Plant::factory()->create([
            'tenant_id' => $tenantB,
        ]);

        $assetB = Asset::factory()->create([
            'tenant_id' => $tenantB,
            'plant_id' => $plantB->id,
        ]);

        $deviceB = Device::factory()->create([
            'tenant_id' => $tenantB,
            'asset_id' => $assetB->id,
        ]);

        $this->getJson('/api/v1/plants')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $plantA->id,
            ])
            ->assertJsonMissing([
                'id' => $plantB->id,
            ]);

        $this->getJson("/api/v1/plants/{$plantB->id}")
            ->assertNotFound();

        $this->getJson('/api/v1/assets')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $assetA->id,
            ])
            ->assertJsonMissing([
                'id' => $assetB->id,
            ]);

        $this->getJson("/api/v1/assets/{$assetB->id}")
            ->assertNotFound();

        $this->getJson('/api/v1/devices')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $deviceA->id,
            ])
            ->assertJsonMissing([
                'id' => $deviceB->id,
            ]);

        $this->getJson("/api/v1/devices/{$deviceB->id}")
            ->assertNotFound();

        $this->postJson('/api/v1/assets', [
            'plant_id' => $plantB->id,
            'name' => 'Cross Tenant Asset',
            'asset_type' => 'INVERTER',
            'serial_number' => 'CROSS-TENANT-001',
            'status' => 'ACTIVE',
            'location' => 'Block X',
        ])->assertNotFound();

        $this->postJson('/api/v1/devices', [
            'asset_id' => $assetB->id,
            'device_type' => 'SMART_METER',
            'serial_number' => 'CROSS-TENANT-DEVICE-001',
            'status' => 'ONLINE',
        ])->assertNotFound();
    }

    public function test_dlq_replay_completes_persisted_event_workflow(): void
    {
        $tenantId = (string) Str::uuid();

        $user = User::factory()
            ->forTenant($tenantId)
            ->create([
                'role' => UserRole::OPERATOR->value,
            ]);

        Sanctum::actingAs($user, [
            TokenAbility::DLQ_READ->value,
            TokenAbility::DLQ_REPLAY->value,
            TokenAbility::TELEMETRY_READ->value,
        ]);

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

        $eventId = (string) Str::uuid();

        $payload = [
            'device_id' => $device->id,
            'recorded_at' => now(),
            'power_kw' => 75.2,
            'temperature' => 45.5,
            'status' => 'OK',
        ];

        $deadLetterEvent = DeadLetterEvent::create([
            'event_id' => $eventId,
            'device_id' => $device->id,
            'original_payload' => $payload,
            'error_type' => 'PROCESSING_ERROR',
            'failure_reason' => 'Temporary telemetry processing failure',
            'attempt_count' => 3,
            'first_failed_at' => now()->subMinutes(5),
            'last_failed_at' => now(),
            'status' => DeadLetterStatus::PENDING,
        ]);

        $response = $this->postJson(
            "/api/v1/dlq/{$deadLetterEvent->id}/replay",
            [
                'event_id' => $deadLetterEvent->event_id,
                'device_id' => $deadLetterEvent->device_id,
                'original_payload' => $deadLetterEvent->original_payload,
            ]
        );

        // $response->dumpHeaders();
        // $response->dump();
        $response->assertOk();

        $this->assertDatabaseHas('dead_letter_events', [
            'id' => $deadLetterEvent->id,
            'status' => DeadLetterStatus::RESOLVED->value,
        ]);

        $this->assertDatabaseHas('telemetry', [
            'device_id' => $device->id,
            'power' => 75.2,
            'temperature' => 45.5,
            'status' => 'OK',
        ]);
    }
}
