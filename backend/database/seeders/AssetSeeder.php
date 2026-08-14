<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Plant;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $assetTypes = ['INVERTER', 'TRACKER', 'TRANSFORMER', 'SOLAR_ARRAY'];

        foreach (Plant::all() as $plant) {
            foreach ($assetTypes as $index => $type) {
                Asset::create([
                    'plant_id' => $plant->id,
                    'name' => $plant->code . '-' . $type,
                    'asset_type' => $type,
                    'serial_number' => strtoupper(substr($plant->code, 0, 3)) . '-' . ($index + 1) . '-' . rand(1000, 9999),
                    'status' => ['ACTIVE', 'ACTIVE', 'MAINTENANCE', 'ACTIVE'][array_rand(['ACTIVE', 'ACTIVE', 'MAINTENANCE', 'ACTIVE'])],
                ]);
            }
        }
    }
}