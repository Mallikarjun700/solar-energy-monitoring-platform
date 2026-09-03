<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlantSeeder::class,
            AssetSeeder::class,
            DeviceSeeder::class,
            TelemetrySeeder::class,
            TelemetryEventSeeder::class,
            AlertSeeder::class,
            DeadLetterEventSeeder::class,
            DeadLetterControllerTestDataSeeder::class,
        ]);
    }
}
