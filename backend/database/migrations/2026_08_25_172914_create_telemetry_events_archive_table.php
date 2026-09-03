<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_telemetry';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $schema = Schema::connection($this->getConnection());

        // The archive table may have been provisioned separately in production.
        if ($schema->hasTable('telemetry_events_archive')) {
            return;
        }

        $schema->create('telemetry_events_archive', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->uuid('event_id')->unique();
            $table->uuid('tenant_id')->index();
            $table->uuid('source_id')->index();

            $table->string('event_type', 100)->index();
            $table->timestampTz('event_timestamp')->index();
            $table->timestampTz('received_at')->nullable();

            $table->unsignedInteger('schema_version');

            $table->jsonb('attributes')->nullable();
            $table->jsonb('payload')->nullable();

            $table->timestampTz('archived_at')->useCurrent();

            $table->index(['tenant_id', 'event_timestamp'],
                'archive_tenant_timestamp_index'
            );

            $table->index(['source_id', 'event_timestamp'],
                'archive_source_timestamp_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('pgsql_telemetry')->dropIfExists('telemetry_events_archive');
    }
};
