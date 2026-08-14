<?php

namespace Tests\Feature;

use App\Models\Plant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_can_be_created(): void
    {
        $plant = Plant::factory()->create();

        $response = $this->postJson('/v1/assets', [
            'plant_id' => $plant->id,
            'name' => 'Inverter 001',
            'asset_type' => 'INVERTER',
            'serial_number' => 'INV-001',
            'status' => 'ACTIVE',
            'location' => 'Block A',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Inverter 001');

        $this->assertDatabaseHas('assets', [
            'name' => 'Inverter 001',
            'plant_id' => $plant->id,
        ]);
    }
}


