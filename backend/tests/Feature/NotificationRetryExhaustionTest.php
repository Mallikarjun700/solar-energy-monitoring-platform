<?php

namespace Tests\Feature;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\NotificationDeliveryStatus;
use App\Models\Alert;
use App\Models\NotificationDelivery;
use App\Services\Notifications\AlertNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationRetryExhaustionTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_notification_remains_failed_after_final_attempt(): void
    {
        config([
            'services.notifications.channel' => 'webhook',
            'services.notifications.webhook.url' => 'https://example.test/webhook',
        ]);

        Http::fake([
            'https://example.test/webhook' => Http::response(['error' => true], 500),
        ]);

        $alert = Alert::factory()->create([
            'severity' => AlertSeverity::HIGH,
            'status' => AlertStatus::OPEN,
        ]);

        $service = app(AlertNotificationService::class);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $service->send($alert);
            } catch (\Throwable $exception) {
                // Expected notification failure.
            }
        }

        $delivery = NotificationDelivery::query()
            ->where('alert_id', $alert->id)
            ->where('channel', 'webhook')
            ->firstOrFail();

        $this->assertSame(
            NotificationDeliveryStatus::FAILED,
            $delivery->status
        );

        $this->assertSame(3, $delivery->attempts);

        $this->assertNotNull($delivery->last_error);

        $this->assertNull($delivery->delivered_at);
    }
}
