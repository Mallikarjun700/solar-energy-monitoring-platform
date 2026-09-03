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
        return [
            'plant_id' => Plant::factory(),
            'name' => $this->faker->word().' Asset',
            'asset_type' => $this->faker->randomElement(['INVERTER', 'TRACKER', 'TRANSFORMER']),
            'serial_number' => 'ASSET-'.$this->faker->unique()->numberBetween(1000, 9999),
            'status' => 'ACTIVE',
        ];
    }
}
