<?php

namespace Tests\Feature;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Mail\AlertNotificationMail;
use App\Models\Alert;
use App\Services\Notifications\AlertNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AlertEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_channel_sends_alert_notification(): void
    {
        config([
            'services.notifications.channel' => 'email',
            'services.notifications.email' => 'alerts@example.com',
        ]);

        Mail::fake();

        $alert = Alert::factory()->create([
            'severity' => AlertSeverity::HIGH,
            'status' => AlertStatus::OPEN,
        ]);

        app(AlertNotificationService::class)
            ->send($alert);

        Mail::assertSent(
            AlertNotificationMail::class,
            function (AlertNotificationMail $mail) use ($alert) {
                return $mail->alert->id === $alert->id
                    && $mail->hasTo('alerts@example.com');
            }
        );
    }

    public function test_email_channel_requires_recipient(): void
    {
        config([
            'services.notifications.channel' => 'email',
            'services.notifications.email' => null,
        ]);

        Mail::fake();

        $alert = Alert::factory()->create();

        $this->expectException(
            \RuntimeException::class
        );

        app(AlertNotificationService::class)
            ->send($alert);

        Mail::assertNothingSent();
    }

    public function test_alert_notification_mail_contains_alert_information(): void
    {
        $alert = Alert::factory()->create([
            'alert_type' => 'high_temperature',
            'message' => 'Temperature exceeded threshold.',
        ]);

        $mail = new AlertNotificationMail($alert);

        $rendered = $mail->render();

        $this->assertStringContainsString(
            'high_temperature',
            $rendered
        );

        $this->assertStringContainsString(
            'Temperature exceeded threshold.',
            $rendered
        );
    }
}
