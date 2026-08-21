<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('queue:status {--json}')] 
#[Description('Display telemetry queue status')]
class QueueStatus extends Command
{
    protected $signature = 'queue:status {--json}';

    protected $description = 'Display telemetry queue status';
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $oldestJob = DB::table('jobs')
            ->where('queue', 'default')
            ->orderBy('created_at')
            ->first();

        $pending = DB::table('jobs')
            ->where('queue', 'default')
            ->count();

        $failed = DB::table('failed_jobs')->count();

        $oldestJobAge = 0;

        if ($oldestJob) {
            $oldestJobAge = now()->diffInSeconds(
                \Carbon\Carbon::createFromTimestamp($oldestJob->created_at)
            );
        }

        $data = [
            'pending_jobs' => $pending,
            'failed_jobs' => $failed,
            'oldest_job_age_seconds' => $oldestJobAge,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($data, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info("Pending jobs: {$pending}");
        $this->info("Failed jobs: {$failed}");

        if ($oldestJob) {
            $this->info("Oldest job age: {$oldestJobAge} seconds");
        } else {
            $this->info('Oldest job age: 0 seconds');
        }

        if ($failed > 0 || $oldestJobAge >= 30) {
            $this->error('Queue health: CRITICAL');
        } elseif ($oldestJobAge >= 10) {
            $this->warn('Queue health: WARNING');
        } else {
            $this->info('Queue health: HEALTHY');
        }

        return self::SUCCESS;
    }
}
