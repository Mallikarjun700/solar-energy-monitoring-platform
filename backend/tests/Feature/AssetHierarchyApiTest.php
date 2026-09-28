<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetHierarchyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_and_traverse_plant_asset_device_hierarchy(): void
    {
        $this->authenticateForApi([
            'plants:read',
            'plants:write',
            'assets:read',
            'assets:write',
            'devices:read',
            'devices:write',
        ]);

        // 1. Create Plant.
        $plantResponse = $this->postJson('/api/v1/plants', [
            'name' => 'Hierarchy Solar Plant',
            'code' => 'HIERARCHY-PLANT-001',
            'location' => 'Bengaluru',
            'capacity_kw' => 5000,
            'status' => 'ACTIVE',
        ]);

        $plantResponse
            ->assertCreated()
            ->assertJsonPath('data.name', 'Hierarchy Solar Plant')
            ->assertJsonPath('data.code', 'HIERARCHY-PLANT-001');

        $plantId = $plantResponse->json('data.id');

        $this->assertNotNull($plantId);

        // 2. Create Asset under the created Plant.
        $assetResponse = $this->postJson('/api/v1/assets', [
            'plant_id' => $plantId,
            'name' => 'Hierarchy Inverter',
            'asset_type' => 'INVERTER',
            'serial_number' => 'HIERARCHY-ASSET-001',
            'status' => 'ACTIVE',
            'location' => 'Block A',
        ]);

        $assetResponse
            ->assertCreated()
            ->assertJsonPath('data.plant_id', $plantId)
            ->assertJsonPath('data.name', 'Hierarchy Inverter');

        $assetId = $assetResponse->json('data.id');

        $this->assertNotNull($assetId);

        // 3. Create Device under the created Asset.
        $deviceResponse = $this->postJson('/api/v1/devices', [
            'asset_id' => $assetId,
            'device_type' => 'METER',
            'serial_number' => 'HIERARCHY-DEVICE-001',
            'status' => 'ONLINE',
        ]);

        $deviceResponse
            ->assertCreated()
            ->assertJsonPath('data.asset_id', $assetId)
            ->assertJsonPath('data.device_type', 'METER');

        $deviceId = $deviceResponse->json('data.id');

        $this->assertNotNull($deviceId);

        // 4. Traverse the hierarchy through the read APIs.
        $this->getJson("/api/v1/plants/{$plantId}")
            ->assertOk()
            ->assertJsonPath('data.id', $plantId);

        $this->getJson("/api/v1/assets?plant_id={$plantId}")
            ->assertOk()
            ->assertJsonPath('data.0.id', $assetId)
            ->assertJsonPath('data.0.plant_id', $plantId);

        $this->getJson("/api/v1/devices?asset_id={$assetId}")
            ->assertOk()
            ->assertJsonPath('data.0.id', $deviceId)
            ->assertJsonPath('data.0.asset_id', $assetId);

        // 5. Verify the complete persisted relationship.
        $this->assertDatabaseHas('plants', [
            'id' => $plantId,
            'code' => 'HIERARCHY-PLANT-001',
        ]);

        $this->assertDatabaseHas('assets', [
            'id' => $assetId,
            'plant_id' => $plantId,
            'serial_number' => 'HIERARCHY-ASSET-001',
        ]);

        $this->assertDatabaseHas('devices', [
            'id' => $deviceId,
            'asset_id' => $assetId,
            'serial_number' => 'HIERARCHY-DEVICE-001',
        ]);
    }
}
