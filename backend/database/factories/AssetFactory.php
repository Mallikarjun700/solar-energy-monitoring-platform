<?php

namespace Database\Factories;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    use HasFactory;
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Solar Farm',
            'code' => fake()->unique()->bothify('PLANT-###'),
            'location' => fake()->city(),
            'capacity_kw' => fake()->randomFloat(2, 1000, 50000),
            'status' => 'ACTIVE',
        ];
    }
}
