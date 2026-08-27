<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupTelemetry extends Command
{
    protected $signature = 'telemetry:cleanup
                            {--execute : Archive eligible telemetry}
                            {--delete : Delete telemetry already archived past retention}';

    protected $description = 'Archive and safely clean up telemetry according to retention policy';

    public function handle(): int
    {
        $startedAt = microtime(true);
        $hotDays = (int) config(
            'telemetry.retention.hot_days',
            90
        );

        $archiveDays = (int) config(
            'telemetry.retention.archive_days',
            365
        );

        $archiveBefore = now()->subDays($hotDays);
        $deleteBefore = now()->subDays($archiveDays);

        $this->info("Hot telemetry retention: {$hotDays} days");
        $this->info("Archive retention: {$archiveDays} days");

        /*
         * -------------------------------------------------------------
         * Report-only mode
         * -------------------------------------------------------------
         */

        $telemetryDb = DB::connection('pgsql_telemetry');

        $eligibleForArchive = $telemetryDb
            ->table('telemetry_events')
            ->where('event_timestamp', '<', $archiveBefore)
            ->count();

        $this->info("Records eligible for archival: {$eligibleForArchive}");

        /*
         * -------------------------------------------------------------
         * Archive
         * -------------------------------------------------------------
         */

        if ($this->option('execute')) {
            $this->info('Archival execution enabled.');

            // Your existing archival implementation goes here.
            //
            // Do not delete source records during this phase.
        }

        /*
         * -------------------------------------------------------------
         * Delete archived telemetry
         * -------------------------------------------------------------
         */

        if ($this->option('delete')) {
            $this->warn('Deletion mode enabled.');

            $this->deleteArchivedTelemetry(
                $telemetryDb,
                $deleteBefore
            );
        }

        if (
            ! $this->option('execute')
            && ! $this->option('delete')
        ) {
            $this->info(
                'Report-only mode. No telemetry was modified.'
            );
        }

        $durationMs = round((microtime(true) - $startedAt) * 1000,2);

        $this->info('Telemetry retention cleanup completed', [
            'hot_retention_days' => $hotDays,
            'archive_retention_days' => $archiveDays,
            'eligible_for_archive' => $eligibleForArchive,
            // 'archived_count' => $archivedCount,
            // 'deleted_count' => $deletedCount,
            'duration_ms' => $durationMs,
        ]);
        return self::SUCCESS;
    }

    private function deleteArchivedTelemetry(
        $telemetryDb,
        $deleteBefore
    ): void {
        $this->info(
            "Checking archived telemetry older than {$deleteBefore->toISOString()}."
        );

        $totalDeleted = 0;

        $telemetryDb
            ->table('telemetry_events_archive')
            ->where('archived_at', '<', $deleteBefore)
            ->orderBy('id')
            ->chunkById(1000, function ($archiveRecords) use (
                $telemetryDb,
                &$totalDeleted
            ) {
                $eventIds = $archiveRecords
                    ->pluck('event_id')
                    ->filter()
                    ->values()
                    ->all();

                if (empty($eventIds)) {
                    return;
                }

                /*
                 * Only delete source telemetry whose event_id
                 * exists in the archive table.
                 */
                $deleted = $telemetryDb
                    ->table('telemetry_events')
                    ->whereIn('event_id', $eventIds)
                    ->delete();

                $totalDeleted += $deleted;

                $this->info(
                    "Deleted {$deleted} telemetry records."
                );
            });

        $this->info(
            "Total telemetry records deleted: {$totalDeleted}"
        );
    }
}