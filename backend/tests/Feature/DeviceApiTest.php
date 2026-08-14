<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Plant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_can_be_created(): void
    {
        $plant = Plant::factory()->create();

        $asset = Asset::factory()->create([
            'plant_id' => $plant->id,
            'serial_number' => 'ASSET-TEST-001',
        ]);

        $response = $this->postJson('/v1/devices', [
            'asset_id' => $asset->id,
            'device_type' => 'THERMAL_SENSOR',
            'serial_number' => 'DEV-001',
            'status' => 'ONLINE',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.device_type', 'THERMAL_SENSOR');

        $this->assertDatabaseHas('devices', [
            'serial_number' => 'DEV-001',
            'asset_id' => $asset->id,
        ]);
    }
}
