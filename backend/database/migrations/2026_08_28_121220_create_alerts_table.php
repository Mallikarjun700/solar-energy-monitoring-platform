<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();

            $table->uuid('tenant_id')->index();

            $table->unsignedBigInteger('plant_id')->nullable()->index();
            $table->unsignedBigInteger('asset_id')->nullable()->index();
            $table->unsignedBigInteger('device_id')->nullable()->index();

            $table->unsignedBigInteger('rule_id')->index();

            $table->uuid('event_id')->nullable()->index();

            $table->string('alert_type', 100)->index();

            $table->string('severity', 20)->index();

            $table->string('status', 30)->index();

            $table->string('message', 500);

            $table->timestampTz('triggered_at');

            $table->timestampTz('acknowledged_at')->nullable();

            $table->timestampTz('resolved_at')->nullable();

            $table->timestamps();

            $table->index(
                ['tenant_id', 'device_id', 'status'],
                'alerts_tenant_device_status_index'
            );

            $table->index(
                ['tenant_id', 'status', 'triggered_at'],
                'alerts_tenant_status_triggered_index'
            );
        });

        /*
         * Prevent multiple active alerts for the same
         * tenant/device/rule combination.
         *
         * PostgreSQL partial unique index.
         */
        DB::statement("
            CREATE UNIQUE INDEX alerts_active_unique
            ON alerts (tenant_id, device_id, rule_id)
            WHERE status IN ('open', 'acknowledged')
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS alerts_active_unique');
        Schema::dropIfExists('alerts');
    }
};
