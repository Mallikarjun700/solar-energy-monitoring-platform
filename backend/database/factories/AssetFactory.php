<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Plant;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        $tenantId = '00000000-0000-0000-0000-000000000001';

        return [
            'tenant_id' => $tenantId,
            'plant_id' => Plant::factory()->state(['tenant_id' => $tenantId]),
            'name' => $this->faker->word().' Asset',
            'asset_type' => $this->faker->randomElement(['INVERTER', 'TRACKER', 'TRANSFORMER']),
            'serial_number' => 'ASSET-'.$this->faker->unique()->numberBetween(1000, 9999),
            'status' => 'ACTIVE',
        ];
    }
}
