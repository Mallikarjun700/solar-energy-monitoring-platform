<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Device;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        $deviceTypes = ['THERMAL_SENSOR', 'POWER_METER', 'VIBRATION_SENSOR', 'GRID_MONITOR'];

        foreach (Asset::all() as $asset) {
            foreach ($deviceTypes as $index => $type) {
                Device::create([
                    'asset_id' => $asset->id,
                    'device_type' => $type,
                    'serial_number' => 'DEV-' . strtoupper(substr($asset->serial_number, 0, 3)) . '-' . ($index + 1) . '-' . rand(10000, 99999),
                    'status' => ['ONLINE', 'OFFLINE', 'FAULT'][array_rand(['ONLINE', 'OFFLINE', 'FAULT'])],
                    'last_seen_at' => now()->subMinutes(rand(5, 180)),
                ]);
            }
        }
    }
}