<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class CleanupTelemetry extends Command
{
    protected $signature = 'telemetry:cleanup
                            {--execute : Archive eligible telemetry}
                            {--delete : Delete archived telemetry past retention}';

    protected $description =
        'Archive and safely clean up telemetry according to retention policy';

    private const CHUNK_SIZE = 1000;

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

        if ($hotDays <= 0 || $archiveDays <= 0) {
            $this->error('Telemetry retention values must be greater than zero.');

            return self::FAILURE;
        }

        if ($archiveDays <= $hotDays) {
            $this->error(
                'Archive retention must be greater than hot retention.'
            );

            return self::FAILURE;
        }

        $archiveBefore = now()->subDays($hotDays);
        $deleteBefore = now()->subDays($archiveDays);

        $telemetryDb = DB::connection('pgsql_telemetry');

        $this->info("Hot telemetry retention: {$hotDays} days");
        $this->info("Archive retention: {$archiveDays} days");

        /*
         * -------------------------------------------------------------
         * Report
         * -------------------------------------------------------------
         */

        $eligibleForArchive = $telemetryDb
            ->table('telemetry_events')
            ->where('event_timestamp', '<', $archiveBefore)
            ->count();

        $eligibleForDeletion = $telemetryDb
            ->table('telemetry_events_archive')
            ->where('archived_at', '<', $deleteBefore)
            ->count();

        $this->info(
            "Records eligible for archival: {$eligibleForArchive}"
        );

        $this->info(
            "Archived records eligible for deletion: {$eligibleForDeletion}"
        );

        /*
         * -------------------------------------------------------------
         * Archive
         * -------------------------------------------------------------
         */

        $archivedCount = 0;

        if ($this->option('execute')) {
            $this->info('Archival execution enabled.');

            $archivedCount = $this->archiveTelemetry(
                $telemetryDb,
                $archiveBefore
            );

            $this->info(
                "Total telemetry records archived: {$archivedCount}"
            );
        }

        /*
         * -------------------------------------------------------------
         * Delete
         * -------------------------------------------------------------
         */

        $deletedCount = 0;

        if ($this->option('delete')) {
            $this->warn('Deletion mode enabled.');

            $deletedCount = $this->deleteArchivedTelemetry(
                $telemetryDb,
                $deleteBefore
            );

            $this->info(
                "Total telemetry records deleted: {$deletedCount}"
            );
        }

        /*
         * -------------------------------------------------------------
         * Report-only mode
         * -------------------------------------------------------------
         */

        if (
            ! $this->option('execute')
            && ! $this->option('delete')
        ) {
            $this->info(
                'Report-only mode. No telemetry was modified.'
            );
        }

        $durationMs = round(
            (microtime(true) - $startedAt) * 1000,
            2
        );

        $this->info(
            'Telemetry retention cleanup completed.'
        );

        $this->line(
            "Hot retention: {$hotDays} days"
        );

        $this->line(
            "Archive retention: {$archiveDays} days"
        );

        $this->line(
            "Eligible for archive: {$eligibleForArchive}"
        );

        $this->line(
            "Archived: {$archivedCount}"
        );

        $this->line(
            "Eligible for deletion: {$eligibleForDeletion}"
        );

        $this->line(
            "Deleted: {$deletedCount}"
        );

        $this->line(
            "Duration: {$durationMs} ms"
        );

        return self::SUCCESS;
    }

    /**
     * Archive telemetry older than the hot-storage retention period.
     *
     * Source records are intentionally NOT deleted here.
     */
    private function archiveTelemetry(
        $telemetryDb,
        $archiveBefore
    ): int {
        $totalArchived = 0;

        $telemetryDb
            ->table('telemetry_events')
            ->where('event_timestamp', '<', $archiveBefore)
            ->orderBy('id')
            ->chunkById(
                self::CHUNK_SIZE,
                function ($events) use (
                    $telemetryDb,
                    &$totalArchived
                ) {
                    $rows = $events->map(
                        function ($event) {
                            return [
                                'event_id' => $event->event_id,
                                'tenant_id' => $event->tenant_id,
                                'source_id' => $event->source_id,
                                'event_type' => $event->event_type,
                                'event_timestamp' => $event->event_timestamp,
                                'received_at' => $event->received_at,
                                'schema_version' => $event->schema_version,
                                'attributes' => $event->attributes,
                                'payload' => $event->payload,
                                'archived_at' => now(),
                            ];
                        }
                    )->all();

                    if (empty($rows)) {
                        return;
                    }

                    /*
                     * event_id is UNIQUE in the archive table.
                     *
                     * insertOrIgnore makes the archival operation
                     * idempotent. Re-running the command will not
                     * create duplicate archive records.
                     */
                    $inserted = $telemetryDb
                        ->table('telemetry_events_archive')
                        ->insertOrIgnore($rows);

                    $totalArchived += $inserted;

                    $this->info(
                        "Processed {$events->count()} telemetry records; "
                        . "archived {$inserted} new records."
                    );
                },
                'id'
            );

        return $totalArchived;
    }

    /**
     * Delete only source telemetry that has already been archived
     * and whose archive retention period has expired.
     */
    private function deleteArchivedTelemetry(
        $telemetryDb,
        $deleteBefore
    ): int {
        $totalDeleted = 0;

        $telemetryDb
            ->table('telemetry_events_archive')
            ->where('archived_at', '<', $deleteBefore)
            ->orderBy('id')
            ->chunkById(
                self::CHUNK_SIZE,
                function ($archiveRecords) use (
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
                     * Delete only source records whose event_id exists
                     * in the archive.
                     */
                    $deleted = $telemetryDb
                        ->table('telemetry_events')
                        ->whereIn('event_id', $eventIds)
                        ->delete();

                    $totalDeleted += $deleted;

                    $this->info(
                        "Deleted {$deleted} hot telemetry records."
                    );
                },
                'id'
            );

        return $totalDeleted;
    }
}