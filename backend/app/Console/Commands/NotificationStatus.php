<?php

namespace App\Console\Commands;

use App\Enums\NotificationDeliveryStatus;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NotificationStatus extends Command
{
    protected $signature = 'notification:status {--json}';

    protected $description = 'Display alert notification delivery status';

    public function handle(): int
    {
        $pending = DB::table('notification_deliveries')
            ->where('status', NotificationDeliveryStatus::PENDING->value)
            ->count();

        $failed = DB::table('notification_deliveries')
            ->where('status', NotificationDeliveryStatus::FAILED->value)
            ->count();

        $sent = DB::table('notification_deliveries')
            ->where('status', NotificationDeliveryStatus::SENT->value)
            ->count();

        $oldestPending = DB::table('notification_deliveries')
            ->where('status', NotificationDeliveryStatus::PENDING->value)
            ->whereNotNull('last_attempted_at')
            ->orderBy('last_attempted_at')
            ->first();

        $oldestPendingAge = 0;

        if ($oldestPending) {
            $oldestPendingAge = now()->diffInSeconds(
                Carbon::parse($oldestPending->last_attempted_at)
            );
        }

        $data = [
            'pending_deliveries' => $pending,
            'failed_deliveries' => $failed,
            'sent_deliveries' => $sent,
            'oldest_pending_age_seconds' => $oldestPendingAge,
        ];

        if ($this->option('json')) {
            $this->line(
                json_encode($data, JSON_PRETTY_PRINT)
            );

            return self::SUCCESS;
        }

        $this->info("Pending deliveries: {$pending}");
        $this->info("Failed deliveries: {$failed}");
        $this->info("Sent deliveries: {$sent}");

        $this->info(
            "Oldest pending delivery age: {$oldestPendingAge} seconds"
        );

        if ($failed > 0 || $oldestPendingAge >= 300) {
            $this->error('Notification health: CRITICAL');
        } elseif ($oldestPendingAge >= 60) {
            $this->warn('Notification health: WARNING');
        } else {
            $this->info('Notification health: HEALTHY');
        }

        return self::SUCCESS;
    }
}
