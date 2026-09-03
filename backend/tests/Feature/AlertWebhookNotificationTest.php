<?php

namespace Tests\Feature;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Services\Notifications\AlertNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AlertWebhookNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_channel_sends_alert(): void
    {
        config([
            'services.notifications.channel' => 'webhook',
            'services.notifications.webhook.url' => 'https://example.test/webhook',
            'services.notifications.webhook.secret' => 'test-secret',
            'services.notifications.webhook.timeout' => 5,
        ]);

        Http::fake([
            'https://example.test/webhook' => Http::response(['ok' => true], 200),
        ]);

        $alert = Alert::factory()->create([
            'severity' => AlertSeverity::HIGH,
            'status' => AlertStatus::OPEN,
        ]);

        app(AlertNotificationService::class)
            ->send($alert);

        Http::assertSent(function ($request) use ($alert) {
            return $request->url() ===
                'https://example.test/webhook'
                && $request['event'] === 'alert.created'
                && $request['alert']['id'] === $alert->id
                && $request->hasHeader('X-Alert-Signature');
        });
    }

    public function test_webhook_failure_throws_exception(): void
    {
        config([
            'services.notifications.channel' => 'webhook',
            'services.notifications.webhook.url' => 'https://example.test/webhook',
        ]);

        Http::fake([
            'https://example.test/webhook' => Http::response(['error' => true], 500),
        ]);

        $alert = Alert::factory()->create();

        $this->expectException(
            \RuntimeException::class
        );

        app(AlertNotificationService::class)
            ->send($alert);
    }

    public function test_webhook_requires_url(): void
    {
        config([
            'services.notifications.channel' => 'webhook',
            'services.notifications.webhook.url' => null,
        ]);

        $alert = Alert::factory()->create();

        $this->expectException(
            \RuntimeException::class
        );

        app(AlertNotificationService::class)
            ->send($alert);
    }
}
