<?php

namespace Database\Factories;

use App\Models\Plant;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlantFactory extends Factory
{
    protected $model = Plant::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' Solar Farm',
            'code' => 'PLANT-' . $this->faker->unique()->numberBetween(1000, 9999),
            'location' => $this->faker->city() . ', ' . $this->faker->state(),
            'capacity_kw' => $this->faker->randomFloat(2, 1000, 100000),
            'status' => $this->faker->randomElement(['ACTIVE', 'INACTIVE', 'MAINTENANCE']),
        ];
    }
}
