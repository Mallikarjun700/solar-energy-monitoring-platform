<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the redundant non-unique event_id index.
     *
     * telemetry_events_event_id_unique already provides indexed
     * event_id lookups while enforcing idempotency.
     */
    public function up(): void
    {
        if (DB::getDefaultConnection() !== 'pgsql_telemetry') {
            return;
        }

        $connection = Schema::connection('pgsql_telemetry');

        if (! $connection->hasTable('telemetry_events')) {
            return;
        }

        $connection->table('telemetry_events', function (Blueprint $table) use ($connection) {
            $indexes = $connection->getConnection()
                ->select("
                    SELECT indexname
                    FROM pg_indexes
                    WHERE schemaname = 'public'
                      AND tablename = 'telemetry_events'
                      AND indexname = 'telemetry_events_event_id_index'
                ");

            if ($indexes !== []) {
                $table->dropIndex('telemetry_events_event_id_index');
            }
        });
    }

    /**
     * Restore the redundant non-unique event_id index.
     */
    public function down(): void
    {
        if (DB::getDefaultConnection() !== 'pgsql_telemetry') {
            return;
        }

        $connection = Schema::connection('pgsql_telemetry');

        if (! $connection->hasTable('telemetry_events')) {
            return;
        }

        $connection->table('telemetry_events', function (Blueprint $table) use ($connection) {
            $indexes = $connection->getConnection()
                ->select("
                    SELECT indexname
                    FROM pg_indexes
                    WHERE schemaname = 'public'
                      AND tablename = 'telemetry_events'
                      AND indexname = 'telemetry_events_event_id_index'
                ");

            if ($indexes === []) {
                $table->index(
                    ['event_id'],
                    'telemetry_events_event_id_index'
                );
            }
        });
    }
};
