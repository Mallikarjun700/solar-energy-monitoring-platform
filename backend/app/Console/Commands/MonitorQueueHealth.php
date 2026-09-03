<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MonitorQueueHealth extends Command
{
    protected $signature = 'queue:monitor-health';

    protected $description = 'Monitor telemetry queue health and report degraded conditions';

    public function handle(): int
    {
        $pending = 0;
        $failed = 0;
        $oldestJob = null;

        if (Schema::hasTable('jobs')) {
            $pending = DB::table('jobs')
                ->where('queue', 'default')
                ->count();

            $oldestJob = DB::table('jobs')
                ->where('queue', 'default')
                ->orderBy('created_at')
                ->first();
        }

        if (Schema::hasTable('failed_jobs')) {
            $failed = DB::table('failed_jobs')->count();
        }

        $oldestJobAge = 0;

        if ($oldestJob) {
            $oldestJobAge = now()->diffInSeconds(
                Carbon::createFromTimestamp($oldestJob->created_at)
            );
        }

        if ($failed > 0 || $oldestJobAge >= 30) {
            logger()->error('Queue health critical', [
                'pending_jobs' => $pending,
                'failed_jobs' => $failed,
                'oldest_job_age_seconds' => $oldestJobAge,
            ]);

            $this->error('Queue health: CRITICAL');

            return self::FAILURE;
        }

        if ($oldestJobAge >= 10) {
            logger()->warning('Queue health warning', [
                'pending_jobs' => $pending,
                'failed_jobs' => $failed,
                'oldest_job_age_seconds' => $oldestJobAge,
            ]);

            $this->warn('Queue health: WARNING');

            return self::SUCCESS;
        }

        logger()->info('Queue health healthy', [
            'pending_jobs' => $pending,
            'failed_jobs' => $failed,
            'oldest_job_age_seconds' => $oldestJobAge,
        ]);

        $this->info('Queue health: HEALTHY');

        return self::SUCCESS;
    }
}
