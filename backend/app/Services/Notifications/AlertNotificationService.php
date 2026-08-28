<?php

namespace App\Services\Notifications;

use App\Contracts\Notifications\NotificationChannel;
use App\Models\Alert;
use InvalidArgumentException;

class AlertNotificationService
{
    public function __construct(
        private readonly LogNotificationChannel $logChannel,
        private readonly EmailNotificationChannel $emailChannel,
    ) {
    }

    public function send(Alert $alert): void
    {
        $channel = config(
            'services.notifications.channel',
            'log'
        );

        match ($channel) {
            'log' => $this->logChannel->send($alert),

            'email' => $this->emailChannel->send($alert),

            default => throw new InvalidArgumentException(
                "Unsupported alert notification channel: {$channel}"
            ),
        };
    }
}