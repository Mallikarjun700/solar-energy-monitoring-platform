<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('telemetry_events', function (Blueprint $table) {
            $table->index(
                ['tenant_id', 'event_timestamp'],
                'telemetry_events_tenant_timestamp_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telemetry_events', function (Blueprint $table) {
            $table->dropIndex('telemetry_events_tenant_timestamp_index');
        });
    }
};
