<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class TelemetryRetentionTest extends TestCase
{

    private string $connection = 'pgsql_telemetry';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'telemetry.retention.hot_days' => 90,
            'telemetry.retention.archive_days' => 365,
        ]);
    }

    public function test_old_telemetry_is_archived(): void
    {
        $db = DB::connection($this->connection);

        $eventId = '550e8400-e29b-41d4-a716-446655440001';

        $db->table('telemetry_events')->insert([
            'event_id' => $eventId,
            'tenant_id' => '550e8400-e29b-41d4-a716-446655440002',
            'source_id' => '550e8400-e29b-41d4-a716-446655440003',
            'event_type' => 'telemetry.power',
            'event_timestamp' => now()->subDays(91),
            'received_at' => now()->subDays(91),
            'schema_version' => 1,
            'attributes' => json_encode([
                'device_id' => 1,
            ]),
            'payload' => json_encode([
                'power_kw' => 25.5,
            ]),
            'created_at' => now()->subDays(91),
        ]);

        Artisan::call('telemetry:cleanup', [
            '--execute' => true,
        ]);

        $this->assertDatabaseHas(
            'telemetry_events_archive',
            [
                'event_id' => $eventId,
            ],
            $this->connection
        );

        $this->assertDatabaseHas(
            'telemetry_events',
            [
                'event_id' => $eventId,
            ],
            $this->connection
        );
    }

    public function test_recent_telemetry_is_not_archived(): void
    {
        $db = DB::connection($this->connection);

        $eventId = '550e8400-e29b-41d4-a716-446655440011';

        $db->table('telemetry_events')->insert([
            'event_id' => $eventId,
            'tenant_id' => '550e8400-e29b-41d4-a716-446655440012',
            'source_id' => '550e8400-e29b-41d4-a716-446655440013',
            'event_type' => 'telemetry.power',
            'event_timestamp' => now()->subDays(30),
            'received_at' => now()->subDays(30),
            'schema_version' => 1,
            'attributes' => json_encode([
                'device_id' => 1,
            ]),
            'payload' => json_encode([
                'power_kw' => 20,
            ]),
            'created_at' => now()->subDays(30),
        ]);

        Artisan::call('telemetry:cleanup', [
            '--execute' => true,
        ]);

        $this->assertDatabaseMissing(
            'telemetry_events_archive',
            [
                'event_id' => $eventId,
            ],
            $this->connection
        );

        $this->assertDatabaseHas(
            'telemetry_events',
            [
                'event_id' => $eventId,
            ],
            $this->connection
        );
    }

    public function test_archival_is_idempotent(): void
    {
        $db = DB::connection($this->connection);

        $eventId = '550e8400-e29b-41d4-a716-446655440021';

        $db->table('telemetry_events')->insert([
            'event_id' => $eventId,
            'tenant_id' => '550e8400-e29b-41d4-a716-446655440022',
            'source_id' => '550e8400-e29b-41d4-a716-446655440023',
            'event_type' => 'telemetry.power',
            'event_timestamp' => now()->subDays(100),
            'received_at' => now()->subDays(100),
            'schema_version' => 1,
            'attributes' => json_encode([
                'device_id' => 1,
            ]),
            'payload' => json_encode([
                'power_kw' => 30,
            ]),
            'created_at' => now()->subDays(100),
        ]);

        Artisan::call('telemetry:cleanup', [
            '--execute' => true,
        ]);

        Artisan::call('telemetry:cleanup', [
            '--execute' => true,
        ]);

        $count = $db
            ->table('telemetry_events_archive')
            ->where('event_id', $eventId)
            ->count();

        $this->assertSame(1, $count);
    }
}