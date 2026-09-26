<?php

namespace Tests;

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
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
        $schema = Schema::connection('pgsql_telemetry');

        if ($schema->hasTable('telemetry_events_archive')) {
            $connection->table('telemetry_events_archive')->delete();
        }

        if ($schema->hasTable('telemetry_events')) {
            $connection->table('telemetry_events')->delete();
        }
    }

    protected function authenticateForApi(array $abilities = []): void
    {
        Sanctum::actingAs(User::factory()->create(), $abilities);
    }
}
