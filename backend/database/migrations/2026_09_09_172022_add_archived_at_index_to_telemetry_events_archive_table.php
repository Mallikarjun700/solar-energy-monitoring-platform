<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->connection !== 'pgsql_telemetry') {
            return;
        }
        Schema::table('telemetry_events_archive', function (Blueprint $table): void {
            $table->index('archived_at', 'telemetry_events_archive_archived_at_index');
        });
    }

    public function down(): void
    {
        if ($this->connection !== 'pgsql_telemetry') {
            return;
        }
        Schema::table('telemetry_events_archive', function (Blueprint $table): void {
            $table->dropIndex('telemetry_events_archive_archived_at_index');
        });
    }
};
