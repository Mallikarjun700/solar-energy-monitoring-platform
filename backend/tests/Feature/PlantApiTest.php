<?php

namespace Tests\Feature;

use App\Models\Plant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlantApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_plant_list_returns_resource_fields(): void
    {
        $plant = Plant::factory()->create();

        $response = $this->getJson('/api/v1/plants');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'name',
                    'code',
                    'location',
                    'capacity_kw',
                    'status',
                    'created_at',
                    'updated_at',
                ]],
            ])
            ->assertJsonPath('data.0.id', $plant->id)
            ->assertJsonPath('data.0.code', $plant->code);
    }

    public function test_plant_creation_returns_resource_fields(): void
    {
        $response = $this->postJson('/api/v1/plants', [
            'name' => 'Rajasthan Solar Farm',
            'code' => 'PLANT-RAJ-001',
            'location' => 'Jodhpur, Rajasthan',
            'capacity_kw' => 2500.50,
            'status' => 'ACTIVE',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Plant created successfully.')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'code',
                    'location',
                    'capacity_kw',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.name', 'Rajasthan Solar Farm')
            ->assertJsonPath('data.code', 'PLANT-RAJ-001');

        $this->assertDatabaseHas('plants', [
            'name' => 'Rajasthan Solar Farm',
            'code' => 'PLANT-RAJ-001',
        ]);
    }
}
