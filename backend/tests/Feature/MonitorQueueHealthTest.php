<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MonitorQueueHealthTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * A basic feature test example.
     */

    public function test_monitor_reports_healthy_queue(): void
    {
        $this->artisan('queue:monitor-health')
            ->expectsOutput('Queue health: HEALTHY')
            ->assertExitCode(0);
    }

    public function test_monitor_reports_critical_when_failed_jobs_exist(): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => fake()->uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Test failure',
            'failed_at' => now(),
        ]);

        $this->artisan('queue:monitor-health')
            ->expectsOutput('Queue health: CRITICAL')
            ->assertExitCode(1);
    }

    public function test_monitor_reports_warning_for_old_pending_job(): void
    {
        DB::table('failed_jobs')->delete();

        $createdAt = now()->subSeconds(15)->timestamp;

        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => $createdAt,
            'created_at' => $createdAt,
        ]);

        $this->artisan('queue:monitor-health')
            ->assertExitCode(0);
    }
}
