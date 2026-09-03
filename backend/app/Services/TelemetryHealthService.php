<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class TelemetryHealthService
{
    public function check(): array
    {
        $database = $this->checkTelemetryDatabase();
        $queue = $this->checkQueue();
        $deadLetterQueue = $this->checkDeadLetterQueue();

        $statuses = [
            $database['status'],
            $queue['status'],
            $deadLetterQueue['status'],
        ];

        $overallStatus = in_array('failed', $statuses, true)
            ? 'degraded'
            : (
                in_array('degraded', $statuses, true)
                    ? 'degraded'
                    : 'healthy'
            );

        return [
            'status' => $overallStatus,
            'checks' => [
                'telemetry_database' => $database['status'],
                'queue' => $queue['status'],
                'dead_letter_queue' => $deadLetterQueue['status'],
            ],
            'queue' => [
                'pending_jobs' => $queue['pending_jobs'],
                'failed_jobs' => $queue['failed_jobs'],
                'oldest_job_age_seconds' => $queue['oldest_job_age_seconds'],
            ],
            'dead_letter_queue' => [
                'pending' => $deadLetterQueue['pending'],
            ],
        ];
    }

    private function checkTelemetryDatabase(): array
    {
        try {
            DB::connection('pgsql_telemetry')
                ->getPdo();

            DB::connection('pgsql_telemetry')
                ->table('telemetry_events')
                ->limit(1)
                ->get();

            return [
                'status' => 'ok',
            ];
        } catch (Throwable $exception) {
            logger()->error('Telemetry database health check failed', [
                'exception' => $exception->getMessage(),
            ]);

            return [
                'status' => 'failed',
            ];
        }
    }

    private function checkQueue(): array
    {
        try {
            $pending = DB::table('jobs')
                ->where('queue', 'default')
                ->count();

            $failed = DB::table('failed_jobs')->count();

            $oldestJob = DB::table('jobs')
                ->where('queue', 'default')
                ->orderBy('created_at')
                ->first();

            $oldestJobAge = 0;

            if ($oldestJob) {
                $oldestJobAge = now()->diffInSeconds(
                    Carbon::createFromTimestamp(
                        $oldestJob->created_at
                    )
                );
            }

            /*
             * Reuse the same thresholds as queue:status
             * and queue:monitor-health.
             */
            if ($failed > 0 || $oldestJobAge >= 30) {
                $status = 'degraded';
            } elseif ($oldestJobAge >= 10) {
                $status = 'degraded';
            } else {
                $status = 'ok';
            }

            return [
                'status' => $status,
                'pending_jobs' => $pending,
                'failed_jobs' => $failed,
                'oldest_job_age_seconds' => $oldestJobAge,
            ];
        } catch (Throwable $exception) {
            logger()->error('Queue health check failed', [
                'exception' => $exception->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'pending_jobs' => 0,
                'failed_jobs' => 0,
                'oldest_job_age_seconds' => 0,
            ];
        }
    }

    private function checkDeadLetterQueue(): array
    {
        try {
            $pending = DB::table('dead_letter_events')
                ->where('status', 'pending')
                ->count();

            return [
                'status' => $pending > 0
                    ? 'degraded'
                    : 'ok',
                'pending' => $pending,
            ];
        } catch (Throwable $exception) {
            logger()->error(
                'Dead letter queue health check failed',
                [
                    'exception' => $exception->getMessage(),
                ]
            );

            return [
                'status' => 'failed',
                'pending' => 0,
            ];
        }
    }
}
