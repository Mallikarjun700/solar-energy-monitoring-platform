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

            if (DB::getDriverName() === 'mysql') {
                $table->string('active_alert_marker')
                    ->nullable()
                    ->storedAs("IF(status IN ('open', 'acknowledged'), 'active', NULL)");
            }

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

        if (DB::getDriverName() === 'mysql') {
            Schema::table('alerts', function (Blueprint $table) {
                $table->unique(
                    ['tenant_id', 'device_id', 'rule_id', 'active_alert_marker'],
                    'alerts_active_unique'
                );
            });
        } else {
            DB::statement("
                CREATE UNIQUE INDEX alerts_active_unique
                ON alerts (tenant_id, device_id, rule_id)
                WHERE status IN ('open', 'acknowledged')
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('alerts', function (Blueprint $table) {
                $table->dropUnique('alerts_active_unique');
                $table->dropColumn('active_alert_marker');
            });
        } else {
            DB::statement('DROP INDEX IF EXISTS alerts_active_unique');
        }

        Schema::dropIfExists('alerts');
    }
};
