<?php

namespace App\Services\Notifications;

use App\Contracts\Notifications\NotificationChannel;
use App\Mail\AlertNotificationMail;
use App\Models\Alert;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class EmailNotificationChannel implements NotificationChannel
{
    public function send(Alert $alert): void
    {
        $email = $this->resolveRecipient($alert);

        if ($email === null) {
            throw new RuntimeException(
                "No notification email configured for alert {$alert->id}."
            );
        }

        Mail::to($email)
            ->send(new AlertNotificationMail($alert));
    }

    private function resolveRecipient(Alert $alert): ?string
    {
        /*
         * Recipient resolution will eventually come from
         * tenant notification preferences.
         *
         * For now use ALERT_NOTIFICATION_EMAIL.
         */
        return config('services.notifications.email');
    }
}