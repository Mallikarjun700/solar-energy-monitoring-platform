<?php

namespace Tests\Feature;

use App\Enums\AlertStatus;
use App\Enums\AlertSeverity;
use App\Enums\NotificationDeliveryStatus;
use App\Models\Alert;
use App\Models\NotificationDelivery;
use App\Services\Notifications\AlertNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationDeliveryIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_delivery_is_recorded(): void
    {
        config([
            'services.notifications.channel' => 'webhook',
            'services.notifications.webhook.url' =>
                'https://example.test/webhook',
        ]);

        Http::fake([
            'https://example.test/webhook' =>
                Http::response(['ok' => true], 200),
        ]);

        $alert = Alert::factory()->create([
            'severity' => AlertSeverity::HIGH,
            'status' => AlertStatus::OPEN,
        ]);

        app(AlertNotificationService::class)->send($alert);

        $delivery = NotificationDelivery::query()
            ->where('alert_id', $alert->id)
            ->where('channel', 'webhook')
            ->firstOrFail();

        $this->assertSame(
            NotificationDeliveryStatus::SENT,
            $delivery->status
        );

        $this->assertSame(1, $delivery->attempts);

        $this->assertNotNull($delivery->delivered_at);
    }

    public function test_successful_delivery_is_not_sent_twice(): void
    {
        config([
            'services.notifications.channel' => 'webhook',
            'services.notifications.webhook.url' =>
                'https://example.test/webhook',
        ]);

        Http::fake([
            'https://example.test/webhook' =>
                Http::response(['ok' => true], 200),
        ]);

        $alert = Alert::factory()->create();

        $service = app(AlertNotificationService::class);

        $service->send($alert);
        $service->send($alert);

        Http::assertSentCount(1);

        $delivery = NotificationDelivery::query()
            ->where('alert_id', $alert->id)
            ->where('channel', 'webhook')
            ->firstOrFail();

        $this->assertSame(1, $delivery->attempts);
        $this->assertSame(
            NotificationDeliveryStatus::SENT,
            $delivery->status
        );
    }

    public function test_failed_delivery_is_recorded(): void
    {
        config([
            'services.notifications.channel' => 'webhook',
            'services.notifications.webhook.url' =>
                'https://example.test/webhook',
        ]);

        Http::fake([
            'https://example.test/webhook' =>
                Http::response(['error' => true], 500),
        ]);

        $alert = Alert::factory()->create();

        $service = app(AlertNotificationService::class);

        try {
            $service->send($alert);
        } catch (\Throwable) {
            // Expected.
        }

        $delivery = NotificationDelivery::query()
            ->where('alert_id', $alert->id)
            ->where('channel', 'webhook')
            ->firstOrFail();

        $this->assertSame(
            NotificationDeliveryStatus::FAILED,
            $delivery->status
        );

        $this->assertSame(1, $delivery->attempts);

        $this->assertNotNull($delivery->last_error);
    }

    public function test_failed_delivery_can_be_retried(): void
    {
        config([
            'services.notifications.channel' => 'webhook',
            'services.notifications.webhook.url' =>
                'https://example.test/webhook',
        ]);

        Http::fakeSequence()
            ->push(['error' => true], 500)
            ->push(['ok' => true], 200);

        $alert = Alert::factory()->create();

        $service = app(AlertNotificationService::class);

        try {
            $service->send($alert);
        } catch (\Throwable) {
            // Expected first attempt.
        }

        $service->send($alert);

        $delivery = NotificationDelivery::query()
            ->where('alert_id', $alert->id)
            ->where('channel', 'webhook')
            ->firstOrFail();

        $this->assertSame(
            NotificationDeliveryStatus::SENT,
            $delivery->status
        );

        $this->assertSame(2, $delivery->attempts);

        Http::assertSentCount(2);
    }
}