<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Device::class;

    public function definition(): array
    {
        $tenantId = '00000000-0000-0000-0000-000000000001';

        return [
            'tenant_id' => $tenantId,
            'asset_id' => Asset::factory()->state(['tenant_id' => $tenantId]),
            'device_type' => fake()->randomElement([
                'INVERTER',
                'METER',
                'SENSOR',
                'WEATHER_STATION',
            ]),
            'serial_number' => fake()->unique()->bothify('DEV-####-????'),
            'status' => fake()->randomElement([
                'ONLINE',
                'OFFLINE',
                'MAINTENANCE',
            ]),
            'last_seen_at' => now(),
        ];
    }
}
