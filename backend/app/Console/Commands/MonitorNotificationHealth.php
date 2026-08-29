<?php

namespace App\Console\Commands;

use App\Enums\NotificationDeliveryStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MonitorNotificationHealth extends Command
{
    protected $signature = 'notification:monitor-health';

    protected $description = 'Monitor alert notification delivery health';

    public function handle(): int
    {
        $pending = 0;
        $failed = 0;
        $oldestPending = null;

        if (Schema::hasTable('notification_deliveries')) {
            $pending = DB::table('notification_deliveries')
                ->where(
                    'status',
                    NotificationDeliveryStatus::PENDING->value
                )
                ->count();

            $failed = DB::table('notification_deliveries')
                ->where(
                    'status',
                    NotificationDeliveryStatus::FAILED->value
                )
                ->count();

            $oldestPending = DB::table('notification_deliveries')
                ->where(
                    'status',
                    NotificationDeliveryStatus::PENDING->value
                )
                ->whereNotNull('last_attempted_at')
                ->orderBy('last_attempted_at')
                ->first();
        }

        $oldestPendingAge = 0;

        if ($oldestPending) {
            $oldestPendingAge = now()->diffInSeconds(
                \Carbon\Carbon::parse(
                    $oldestPending->last_attempted_at
                )
            );
        }

        if ($failed > 0 || $oldestPendingAge >= 300) {
            logger()->error('Notification health critical', [
                'pending_deliveries' => $pending,
                'failed_deliveries' => $failed,
                'oldest_pending_age_seconds' => $oldestPendingAge,
            ]);

            $this->error('Notification health: CRITICAL');

            return self::FAILURE;
        }

        if ($oldestPendingAge >= 60) {
            logger()->warning('Notification health warning', [
                'pending_deliveries' => $pending,
                'failed_deliveries' => $failed,
                'oldest_pending_age_seconds' => $oldestPendingAge,
            ]);

            $this->warn('Notification health: WARNING');

            return self::SUCCESS;
        }

        logger()->info('Notification health healthy', [
            'pending_deliveries' => $pending,
            'failed_deliveries' => $failed,
            'oldest_pending_age_seconds' => $oldestPendingAge,
        ]);

        $this->info('Notification health: HEALTHY');

        return self::SUCCESS;
    }
}