<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Plant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_index_returns_resource_fields(): void
    {
        $asset = Asset::factory()->create();

        $response = $this->getJson('/api/v1/assets');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'plant_id',
                    'name',
                    'asset_type',
                    'serial_number',
                    'status',
                    'location',
                    'created_at',
                    'updated_at',
                ]],
            ])
            ->assertJsonPath('data.0.id', $asset->id);
    }

    public function test_asset_store_returns_resource_fields(): void
    {
        $plant = Plant::factory()->create();

        $response = $this->postJson('/api/v1/assets', [
            'plant_id' => $plant->id,
            'name' => 'Inverter 001',
            'asset_type' => 'INVERTER',
            'serial_number' => 'INV-001',
            'status' => 'ACTIVE',
            'location' => 'Block A',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'plant_id',
                    'name',
                    'asset_type',
                    'serial_number',
                    'status',
                    'location',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.name', 'Inverter 001');

        $this->assertDatabaseHas('assets', [
            'name' => 'Inverter 001',
            'plant_id' => $plant->id,
        ]);
    }

    public function test_asset_show_returns_resource_fields(): void
    {
        $asset = Asset::factory()->create();

        $response = $this->getJson("/api/v1/assets/{$asset->id}");

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'plant_id',
                    'name',
                    'asset_type',
                    'serial_number',
                    'status',
                    'location',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.id', $asset->id);
    }

    public function test_asset_update_returns_resource_fields(): void
    {
        $asset = Asset::factory()->create();

        $response = $this->patchJson("/api/v1/assets/{$asset->id}", [
            'status' => 'MAINTENANCE',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'plant_id',
                    'name',
                    'asset_type',
                    'serial_number',
                    'status',
                    'location',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.id', $asset->id)
            ->assertJsonPath('data.status', 'MAINTENANCE');
    }
}
