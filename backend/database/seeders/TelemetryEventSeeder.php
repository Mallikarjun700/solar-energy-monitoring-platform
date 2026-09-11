<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TelemetryEventSeeder extends Seeder
{
    public function run(): void
    {
        // Only seed PostgreSQL, skip for MySQL
        if (DB::getDefaultConnection() !== 'pgsql_telemetry') {
            echo 'Skipping TelemetryEventSeeder on non-PostgreSQL connections'.PHP_EOL;

            return;
        }

        $now = now();
        $tenantId = '11111111-1111-4111-8111-111111111111';
        $sourceIds = [
            '22222222-2222-4222-8222-222222222222',
            '22222222-2222-4222-8222-222222222223',
            '22222222-2222-4222-8222-222222222224',
        ];
        $sourceId = $sourceIds[0];

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

        foreach ($sourceIds as $sourceIndex => $sourceId) {
            for ($readingIndex = 0; $readingIndex < 30; $readingIndex++) {
                $minutesAgo = ($readingIndex * 24) + ($sourceIndex * 8);
                $eventTimestamp = $now->copy()->subMinutes($minutesAgo + 2);

                $events[] = [
                    'event_id' => sprintf(
                        '33333333-3333-4333-8333-%012d',
                        334 + ($sourceIndex * 30) + $readingIndex,
                    ),
                    'tenant_id' => $tenantId,
                    'source_id' => $sourceId,
                    'event_type' => $readingIndex % 3 === 0
                        ? 'telemetry.power'
                        : ($readingIndex % 3 === 1 ? 'telemetry.temperature' : 'telemetry.received'),
                    'event_timestamp' => $eventTimestamp,
                    'received_at' => $eventTimestamp->copy()->addSeconds(8),
                    'schema_version' => 1,
                    'attributes' => json_encode([
                        'device_type' => $readingIndex % 3 === 1 ? 'THERMAL_SENSOR' : 'POWER_METER',
                        'site' => 'solar-site-'.($sourceIndex + 1),
                    ]),
                    'payload' => json_encode([
                        'device_id' => ($sourceIndex * 30) + $readingIndex + 1,
                        'power_kw' => round(38 + ($sourceIndex * 4) + (($readingIndex * 1.7) % 18), 2),
                        'temperature_c' => round(27 + (($readingIndex * 0.8) % 16), 2),
                        'voltage' => round(228 + (($readingIndex * 0.6) % 8), 2),
                        'status' => $readingIndex % 11 === 0 ? 'WARNING' : 'NORMAL',
                    ]),
                    'created_at' => $eventTimestamp->copy()->addSeconds(8),
                ];
            }
        }

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
