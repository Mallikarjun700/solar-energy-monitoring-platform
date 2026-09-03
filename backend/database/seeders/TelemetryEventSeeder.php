<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TelemetryEventSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $tenantId = '11111111-1111-4111-8111-111111111111';
        $sourceId = '22222222-2222-4222-8222-222222222222';

        $events = [
            [
                'event_id' => '33333333-3333-4333-8333-333333333331',
                'tenant_id' => $tenantId,
                'source_id' => $sourceId,
                'event_type' => 'telemetry.received',
                'event_timestamp' => $now->copy()->subMinutes(10),
                'received_at' => $now->copy()->subMinutes(9),
                'schema_version' => 1,
                'attributes' => json_encode(['device_type' => 'POWER_METER']),
                'payload' => json_encode([
                    'device_id' => 1,
                    'power_kw' => 42.7,
                    'voltage' => 230.4,
                ]),
                'created_at' => $now,
            ],
            [
                'event_id' => '33333333-3333-4333-8333-333333333332',
                'tenant_id' => $tenantId,
                'source_id' => $sourceId,
                'event_type' => 'telemetry.received',
                'event_timestamp' => $now->copy()->subMinutes(5),
                'received_at' => $now->copy()->subMinutes(4),
                'schema_version' => 1,
                'attributes' => json_encode(['device_type' => 'THERMAL_SENSOR']),
                'payload' => json_encode([
                    'device_id' => 2,
                    'temperature_c' => 36.2,
                    'status' => 'NORMAL',
                ]),
                'created_at' => $now,
            ],
            [
                'event_id' => '33333333-3333-4333-8333-333333333333',
                'tenant_id' => $tenantId,
                'source_id' => $sourceId,
                'event_type' => 'telemetry.received',
                'event_timestamp' => $now->subMinute(),
                'received_at' => $now,
                'schema_version' => 1,
                'attributes' => json_encode(['device_type' => 'GRID_MONITOR']),
                'payload' => json_encode([
                    'device_id' => 3,
                    'frequency_hz' => 50.01,
                    'status' => 'NORMAL',
                ]),
                'created_at' => $now,
            ],
        ];

        DB::connection('pgsql_telemetry')
            ->table('telemetry_events')
            ->upsert($events, ['event_id'], [
                'tenant_id',
                'source_id',
                'event_type',
                'event_timestamp',
                'received_at',
                'schema_version',
                'attributes',
                'payload',
            ]);
    }
}
