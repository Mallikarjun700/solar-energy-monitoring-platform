<?php

namespace App\Services\Notifications;

use App\Contracts\Notifications\NotificationChannel;
use App\Models\Alert;

class LogNotificationChannel implements NotificationChannel
{
    public function send(Alert $alert): void
    {
        logger()->info('Alert notification delivered', [
            'alert_id' => $alert->id,
            'tenant_id' => $alert->tenant_id,
            'device_id' => $alert->device_id,
            'severity' => $alert->severity->value,
            'alert_type' => $alert->alert_type,
            'message' => $alert->message,
        ]);
    }
}
