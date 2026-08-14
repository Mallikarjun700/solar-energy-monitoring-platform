<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Telemetry;
use Illuminate\Database\Seeder;

class TelemetrySeeder extends Seeder
{
    public function run(): void
    {
        foreach (Device::all() as $device) {
            for ($i = 0; $i < 10; $i++) {
                Telemetry::create([
                    'device_id' => $device->id,
                    'recorded_at' => now()->subMinutes(rand(1, 300)),
                    'temperature' => rand(20, 60) + (rand(0, 99) / 100),
                    'voltage' => rand(200, 260) + (rand(0, 99) / 100),
                    'current' => rand(10, 80) + (rand(0, 99) / 100),
                    'power' => rand(1000, 50000) / 10,
                    'energy_generated' => rand(1000, 50000) / 10,
                    'status' => ['NORMAL', 'WARN', 'FAULT'][array_rand(['NORMAL', 'WARN', 'FAULT'])],
                ]);
            }
        }
    }
}