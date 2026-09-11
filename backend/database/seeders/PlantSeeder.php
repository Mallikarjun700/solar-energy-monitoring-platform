<?php

namespace Database\Seeders;

use App\Models\Plant;
use Illuminate\Database\Seeder;

class PlantSeeder extends Seeder
{
    public function run(): void
    {
        $plants = [
            [
                'tenant_id' => '11111111-1111-4111-8111-111111111111',
                'name' => 'Mysore Solar Farm',
                'code' => 'MSF-001',
                'location' => 'Mysore, Karnataka',
                'capacity_kw' => 50000.00,
                'status' => 'ACTIVE',
            ],
            [
                'tenant_id' => '11111111-1111-4111-8111-111111111111',
                'name' => 'Bengaluru Solar Park',
                'code' => 'BSP-002',
                'location' => 'Bengaluru, Karnataka',
                'capacity_kw' => 75000.00,
                'status' => 'ACTIVE',
            ],
            [
                'tenant_id' => '11111111-1111-4111-8111-111111111111',
                'name' => 'Shimoga PV Unit',
                'code' => 'SPU-003',
                'location' => 'Shimoga, Karnataka',
                'capacity_kw' => 32000.00,
                'status' => 'MAINTENANCE',
            ],
            [
                'tenant_id' => '11111111-1111-4111-8111-111111111111',
                'name' => 'Hubballi Renewable Hub',
                'code' => 'HRH-004',
                'location' => 'Hubballi, Karnataka',
                'capacity_kw' => 98000.00,
                'status' => 'ACTIVE',
            ],
            [
                'tenant_id' => '11111111-1111-4111-8111-111111111111',
                'name' => 'Coorg Solar Field',
                'code' => 'CSF-005',
                'location' => 'Coorg, Karnataka',
                'capacity_kw' => 41000.00,
                'status' => 'INACTIVE',
            ],
        ];

        foreach ($plants as $plant) {
            Plant::updateOrCreate(['code' => $plant['code']], $plant);
        }
    }
}
