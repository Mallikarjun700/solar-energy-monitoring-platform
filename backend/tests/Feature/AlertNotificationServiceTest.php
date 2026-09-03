<?php

namespace Tests\Feature;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Services\Notifications\AlertNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AlertNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_channel_delivers_alert_notification(): void
    {
        config([
            'services.notifications.channel' => 'log',
        ]);

        Log::spy();

        $alert = Alert::factory()->create([
            'severity' => AlertSeverity::HIGH,
            'status' => AlertStatus::OPEN,
        ]);

        app(AlertNotificationService::class)
            ->send($alert);

        Log::shouldHaveReceived('info')
            ->once()
            ->with(
                'Alert notification delivered',
                \Mockery::on(function (array $context) use ($alert) {
                    return $context['alert_id'] === $alert->id
                        && $context['tenant_id'] === $alert->tenant_id
                        && $context['device_id'] === $alert->device_id;
                })
            );
    }

    public function test_unsupported_notification_channel_is_rejected(): void
    {
        config([
            'services.notifications.channel' => 'unknown',
        ]);

        $alert = Alert::factory()->create();

        $this->expectException(
            \InvalidArgumentException::class
        );

        app(AlertNotificationService::class)
            ->send($alert);
    }
}
