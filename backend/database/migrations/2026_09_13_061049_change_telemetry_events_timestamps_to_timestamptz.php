<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection('pgsql_telemetry');

        // This migration is specifically for PostgreSQL telemetry storage.
        if ($connection->getDriverName() !== 'pgsql') {
            return;
        }

        $connection->statement(
            "ALTER TABLE telemetry_events
             ALTER COLUMN event_timestamp TYPE TIMESTAMPTZ
             USING event_timestamp AT TIME ZONE 'UTC'"
        );

        $connection->statement(
            "ALTER TABLE telemetry_events
             ALTER COLUMN received_at TYPE TIMESTAMPTZ
             USING received_at AT TIME ZONE 'UTC'"
        );

        $connection->statement(
            "ALTER TABLE telemetry_events
             ALTER COLUMN created_at TYPE TIMESTAMPTZ
             USING created_at AT TIME ZONE 'UTC'"
        );
    }

    public function down(): void
    {
        // Skip if not PostgreSQL telemetry database
        if (DB::getDefaultConnection() !== 'pgsql_telemetry') {
            return;
        }

        $connection = DB::connection('pgsql_telemetry');

        if ($connection->getDriverName() !== 'pgsql') {
            return;
        }

        $connection->statement(
            "ALTER TABLE telemetry_events
             ALTER COLUMN event_timestamp TYPE TIMESTAMP
             USING event_timestamp AT TIME ZONE 'UTC'"
        );

        $connection->statement(
            "ALTER TABLE telemetry_events
             ALTER COLUMN received_at TYPE TIMESTAMP
             USING received_at AT TIME ZONE 'UTC'"
        );

        $connection->statement(
            "ALTER TABLE telemetry_events
             ALTER COLUMN created_at TYPE TIMESTAMP
             USING created_at AT TIME ZONE 'UTC'"
        );
    }
};
