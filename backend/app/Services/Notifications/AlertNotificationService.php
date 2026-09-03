<?php

namespace App\Services\Notifications;

use App\Enums\NotificationDeliveryStatus;
use App\Models\Alert;
use App\Models\NotificationDelivery;
use App\Models\NotificationPreference;
use InvalidArgumentException;

class AlertNotificationService
{
    public function __construct(
        private readonly LogNotificationChannel $logChannel,
        private readonly EmailNotificationChannel $emailChannel,
        private readonly WebhookNotificationChannel $webhookChannel,
        private readonly NotificationPreferenceResolver $preferenceResolver,
    ) {}

    public function send(Alert $alert): void
    {
        $preference = $this->preferenceResolver->resolve($alert);

        if ($preference && ! $preference->isEnabledForSeverity($alert->severity->value)) {
            logger()->info('Alert notification skipped by tenant preference', [
                'alert_id' => $alert->id,
                'tenant_id' => $alert->tenant_id,
                'severity' => $alert->severity->value,
                'channel' => $preference?->channel,
                'reason' => 'tenant_preference',
            ]);

            return;
        }

        $channel = $preference?->channel ?? config('services.notifications.channel', 'log');

        $delivery = NotificationDelivery::firstOrCreate(
            [
                'alert_id' => $alert->id,
                'channel' => $channel,
            ],
            [
                'status' => NotificationDeliveryStatus::PENDING,
                'attempts' => 0,
            ]
        );

        /*
         * A successfully delivered notification must never
         * be sent again for the same alert/channel pair.
         */
        if ($delivery->status === NotificationDeliveryStatus::SENT) {
            logger()->info('Notification delivery skipped because it was already sent', [
                'alert_id' => $alert->id,
                'tenant_id' => $alert->tenant_id,
                'channel' => $channel,
                'delivery_id' => $delivery->id,
                'attempts' => $delivery->attempts,
            ]);

            return;
        }

        $this->markAttempt($delivery);

        try {
            $this->sendThroughChannel($channel, $alert, $preference);

            $delivery->update([
                'status' => NotificationDeliveryStatus::SENT,
                'delivered_at' => now(),
                'last_error' => null,
            ]);

            logger()->debug('Notification delivery recorded', [
                'alert_id' => $alert->id,
                'channel' => $channel,
                'delivery_id' => $delivery->id,
                'attempts' => $delivery->attempts,
            ]);
        } catch (\Throwable $exception) {
            $delivery->update([
                'status' => NotificationDeliveryStatus::FAILED,
                'last_error' => $exception->getMessage(),
            ]);

            logger()->error('Notification delivery failed', [
                'alert_id' => $alert->id,
                'tenant_id' => $alert->tenant_id,
                'channel' => $channel,
                'delivery_id' => $delivery->id,
                'attempts' => $delivery->attempts,
                'status' => NotificationDeliveryStatus::FAILED->value,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function markAttempt(NotificationDelivery $delivery): void
    {
        $now = now();

        $delivery->update([
            'status' => NotificationDeliveryStatus::PENDING,
            'attempts' => $delivery->attempts + 1,
            'first_attempted_at' => $delivery->first_attempted_at ?? $now,
            'last_attempted_at' => $now,
        ]);

        $delivery->refresh();
    }

    private function channel(string $channel): object
    {
        return match ($channel) {
            'log' => $this->logChannel,
            'email' => $this->emailChannel,
            'webhook' => $this->webhookChannel,

            default => throw new InvalidArgumentException(
                "Unsupported alert notification channel: {$channel}"
            ),
        };
    }

    private function sendThroughChannel(string $channel, Alert $alert, ?NotificationPreference $preference): void
    {
        match ($channel) {
            'log' => $this->logChannel->send($alert),

            'email' => $this->emailChannel->send(
                $alert,
                $preference
            ),

            'webhook' => $this->webhookChannel->send(
                $alert,
                $preference
            ),

            default => throw new InvalidArgumentException(
                "Unsupported alert notification channel: {$channel}"
            ),
        };
    }
}
