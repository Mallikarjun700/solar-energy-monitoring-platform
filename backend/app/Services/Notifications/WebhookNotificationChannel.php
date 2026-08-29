<?php

namespace App\Services\Notifications;

use App\Contracts\Notifications\NotificationChannel;
use App\Models\Alert;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use App\Models\NotificationPreference;

class WebhookNotificationChannel implements NotificationChannel
{
    public function send(Alert $alert,?NotificationPreference $preference = null): void {
        $url = $preference?->webhook_url ?? config('services.notifications.webhook.url');

        if (! $url) {
            throw new RuntimeException(
                'Alert notification webhook URL is not configured.'
            );
        }

        $payload = [
            'event' => 'alert.created',
            'alert' => [
                'id' => $alert->id,
                'tenant_id' => $alert->tenant_id,
                'plant_id' => $alert->plant_id,
                'asset_id' => $alert->asset_id,
                'device_id' => $alert->device_id,
                'rule_id' => $alert->rule_id,
                'event_id' => $alert->event_id,
                'alert_type' => $alert->alert_type,
                'severity' => $alert->severity->value,
                'status' => $alert->status->value,
                'message' => $alert->message,
                'triggered_at' => $alert->triggered_at?->toISOString(),
            ],
        ];

        $request = Http::timeout(
            (int) config('services.notifications.webhook.timeout',5)
        );

        $secret = $preference?->webhook_secret ?? config('services.notifications.webhook.secret');

        if ($secret) {
            $request = $request->withHeaders([
                'X-Alert-Signature' => hash_hmac(
                    'sha256',
                    json_encode($payload, JSON_THROW_ON_ERROR),
                    $secret
                ),
            ]);
        }

        $response = $request->post($url, $payload);

        if ($response->failed()) {
            throw new RuntimeException(
                "Alert webhook delivery failed with HTTP {$response->status()}."
            );
        }

        logger()->info('Alert webhook delivered', [
            'alert_id' => $alert->id,
            'tenant_id' => $alert->tenant_id,
            'status' => $response->status(),
        ]);
    }
}