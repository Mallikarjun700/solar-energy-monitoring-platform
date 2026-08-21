<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QueueStatusTest extends TestCase
{
    /**
     * A basic feature test example.
    */
    use RefreshDatabase;

    public function test_queue_status_reports_healthy_when_queue_is_empty_and_no_failures_exist(): void
    {
        $this->artisan('queue:status')
            ->expectsOutput('Pending jobs: 0')
            ->expectsOutput('Failed jobs: 0')
            ->expectsOutput('Oldest job age: 0 seconds')
            ->expectsOutput('Queue health: HEALTHY')
            ->assertExitCode(0);
    }

    public function test_queue_status_reports_warning_when_jobs_are_pending(): void
    {
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        $this->artisan('queue:status')
            ->expectsOutput('Pending jobs: 1')
            ->expectsOutputToContain('Queue health:')
            ->assertExitCode(0);
    }

    public function test_queue_status_json_returns_expected_structure(): void
    {
        $exitCode = Artisan::call('queue:status', ['--json' => true]);

        $this->assertSame(0, $exitCode);

        $output = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('pending_jobs', $output);
        $this->assertArrayHasKey('failed_jobs', $output);
        $this->assertArrayHasKey('oldest_job_age_seconds', $output);
    }

    public function test_queue_status_reports_critical_when_failed_jobs_exist(): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => fake()->uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Test failure',
            'failed_at' => now(),
        ]);

        $this->artisan('queue:status')
            ->expectsOutput('Failed jobs: 1')
            ->expectsOutput('Oldest job age: 0 seconds')
            ->expectsOutput('Queue health: CRITICAL')
            ->assertExitCode(0);
    }
}
