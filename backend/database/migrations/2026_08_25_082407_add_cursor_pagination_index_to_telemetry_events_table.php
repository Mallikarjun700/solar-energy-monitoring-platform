<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_telemetry')->table(
            'telemetry_events',
            function (Blueprint $table) {
                $table->index(
                    ['event_timestamp', 'id'],
                    'telemetry_events_cursor_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::connection('pgsql_telemetry')->table(
            'telemetry_events',
            function (Blueprint $table) {
                $table->dropIndex('telemetry_events_cursor_index');
            }
        );
    }
};
