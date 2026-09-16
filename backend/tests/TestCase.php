<?php

namespace Tests;

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $telemetrySchema = Schema::connection('pgsql_telemetry');

        if (! $telemetrySchema->hasTable('telemetry_events')) {
            $telemetrySchema->create('telemetry_events', function (Blueprint $table): void {
                $table->id();
                $table->uuid('event_id')->unique();
                $table->uuid('tenant_id')->index();
                $table->uuid('source_id')->index();
                $table->string('event_type', 100);
                $table->timestamp('event_timestamp')->index();
                $table->timestamp('received_at');
                $table->unsignedInteger('schema_version');
                $table->json('attributes')->nullable();
                $table->json('payload')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! $telemetrySchema->hasTable('telemetry_events_archive')) {
            $telemetrySchema->create('telemetry_events_archive', function (Blueprint $table): void {
                $table->id();
                $table->uuid('event_id')->unique();
                $table->uuid('tenant_id')->index();
                $table->uuid('source_id')->index();
                $table->string('event_type', 100)->index();
                $table->timestamp('event_timestamp')->index();
                $table->timestamp('received_at')->nullable();
                $table->unsignedInteger('schema_version');
                $table->json('attributes')->nullable();
                $table->json('payload')->nullable();
                $table->timestamp('archived_at')->useCurrent();
            });
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetTelemetryDatabase();
    }

    protected function resetTelemetryDatabase(): void
    {
        $connection = DB::connection('pgsql_telemetry');

        $connection->table('telemetry_events_archive')->delete();
        $connection->table('telemetry_events')->delete();
    }

    protected function authenticateForApi(array $abilities = []): void
    {
        Sanctum::actingAs(User::factory()->create(), $abilities);
    }
}
