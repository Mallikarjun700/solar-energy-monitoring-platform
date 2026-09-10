<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plants', function (Blueprint $table): void {
            $table->uuid('tenant_id')
                ->after('id')
                ->nullable()
                ->index();
        });

        Schema::table('assets', function (Blueprint $table): void {
            $table->uuid('tenant_id')
                ->after('id')
                ->nullable()
                ->index();
        });

        Schema::table('devices', function (Blueprint $table): void {
            $table->uuid('tenant_id')
                ->after('id')
                ->nullable()
                ->index();
        });

        Schema::table('telemetry', function (Blueprint $table): void {
            $table->uuid('tenant_id')
                ->after('id')
                ->nullable()
                ->index();
        });

        Schema::table('plants', function (Blueprint $table): void {
            $table->index(
                ['tenant_id', 'status'],
                'plants_tenant_status_index'
            );
        });

        Schema::table('assets', function (Blueprint $table): void {
            $table->index(
                ['tenant_id', 'plant_id'],
                'assets_tenant_plant_index'
            );
        });

        Schema::table('devices', function (Blueprint $table): void {
            $table->index(
                ['tenant_id', 'asset_id'],
                'devices_tenant_asset_index'
            );
        });

        Schema::table('telemetry', function (Blueprint $table): void {
            $table->index(
                ['tenant_id', 'device_id', 'recorded_at'],
                'telemetry_tenant_device_recorded_at_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('telemetry', function (Blueprint $table): void {
            $table->dropIndex('telemetry_tenant_device_recorded_at_index');
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('devices', function (Blueprint $table): void {
            $table->dropIndex('devices_tenant_asset_index');
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('assets', function (Blueprint $table): void {
            $table->dropIndex('assets_tenant_plant_index');
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('plants', function (Blueprint $table): void {
            $table->dropIndex('plants_tenant_status_index');
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
