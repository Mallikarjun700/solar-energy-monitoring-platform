<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDefaultConnection() !== 'pgsql_telemetry') {
            return;
        }

        Schema::connection('pgsql_telemetry')->table(
            'telemetry_events',
            function (Blueprint $table) {
                $table->index(
                    ['tenant_id', 'event_timestamp'],
                    'telemetry_events_tenant_timestamp_index'
                );
            }
        );
    }

    public function down(): void
    {
        if (DB::getDefaultConnection() !== 'pgsql_telemetry') {
            return;
        }

        Schema::connection('pgsql_telemetry')->table(
            'telemetry_events',
            function (Blueprint $table) {
                $table->dropIndex('telemetry_events_tenant_timestamp_index');
            }
        );
    }
};
