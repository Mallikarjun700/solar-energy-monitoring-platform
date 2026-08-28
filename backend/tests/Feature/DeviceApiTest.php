<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Plant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateForApi();
    }

    public function test_device_index_returns_resource_fields(): void
    {
        $device = Asset::factory()->create()->devices()->create([
            'device_type' => 'THERMAL_SENSOR',
            'serial_number' => 'DEV-INDEX-001',
            'status' => 'ONLINE',
        ]);

        $response = $this->getJson('/api/v1/devices');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'asset_id',
                    'device_type',
                    'serial_number',
                    'status',
                    'last_seen_at',
                    'created_at',
                    'updated_at',
                ]],
            ])
            ->assertJsonPath('data.0.id', $device->id);
    }

    public function test_device_store_returns_resource_fields(): void
    {
        $plant = Plant::factory()->create();

        $asset = Asset::factory()->create([
            'plant_id' => $plant->id,
            'serial_number' => 'ASSET-TEST-001',
        ]);

        $response = $this->postJson('/api/v1/devices', [
            'asset_id' => $asset->id,
            'device_type' => 'THERMAL_SENSOR',
            'serial_number' => 'DEV-001',
            'status' => 'ONLINE',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'asset_id',
                    'device_type',
                    'serial_number',
                    'status',
                    'last_seen_at',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.device_type', 'THERMAL_SENSOR');

        $this->assertDatabaseHas('devices', [
            'serial_number' => 'DEV-001',
            'asset_id' => $asset->id,
        ]);
    }

    public function test_device_show_returns_resource_fields(): void
    {
        $device = Asset::factory()->create()->devices()->create([
            'device_type' => 'THERMAL_SENSOR',
            'serial_number' => 'DEV-SHOW-001',
            'status' => 'ONLINE',
        ]);

        $response = $this->getJson("/api/v1/devices/{$device->id}");

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'asset_id',
                    'device_type',
                    'serial_number',
                    'status',
                    'last_seen_at',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.id', $device->id);
    }

    public function test_device_update_returns_resource_fields(): void
    {
        $device = Asset::factory()->create()->devices()->create([
            'device_type' => 'THERMAL_SENSOR',
            'serial_number' => 'DEV-UPDATE-001',
            'status' => 'ONLINE',
        ]);

        $response = $this->patchJson("/api/v1/devices/{$device->id}", [
            'status' => 'MAINTENANCE',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'asset_id',
                    'device_type',
                    'serial_number',
                    'status',
                    'last_seen_at',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.id', $device->id)
            ->assertJsonPath('data.status', 'MAINTENANCE');
    }
}
