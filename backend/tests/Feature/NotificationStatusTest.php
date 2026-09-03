<?php

namespace Tests\Feature;

use App\Enums\AlertStatus;
use App\Enums\NotificationDeliveryStatus;
use App\Models\Alert;
use App\Models\NotificationDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class NotificationStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_status_reports_healthy_when_no_failures_exist(): void
    {
        $this->artisan('notification:status')
            ->expectsOutput('Pending deliveries: 0')
            ->expectsOutput('Failed deliveries: 0')
            ->expectsOutput('Sent deliveries: 0')
            ->expectsOutput('Notification health: HEALTHY')
            ->assertExitCode(0);
    }

    public function test_notification_status_reports_failed_deliveries(): void
    {
        $alert = Alert::factory()->create([
            'status' => AlertStatus::OPEN,
        ]);

        NotificationDelivery::create([
            'alert_id' => $alert->id,
            'channel' => 'webhook',
            'status' => NotificationDeliveryStatus::FAILED,
            'attempts' => 3,
            'last_attempted_at' => now(),
            'last_error' => 'Webhook failed',
        ]);

        $this->artisan('notification:status')
            ->expectsOutput('Failed deliveries: 1')
            ->expectsOutput('Notification health: CRITICAL')
            ->assertExitCode(0);
    }

    public function test_notification_status_reports_pending_delivery(): void
    {
        $alert = Alert::factory()->create([
            'status' => AlertStatus::OPEN,
        ]);

        NotificationDelivery::create([
            'alert_id' => $alert->id,
            'channel' => 'email',
            'status' => NotificationDeliveryStatus::PENDING,
            'attempts' => 1,
            'last_attempted_at' => now(),
        ]);

        $this->artisan('notification:status')
            ->expectsOutput('Pending deliveries: 1')
            ->expectsOutput('Notification health: HEALTHY')
            ->assertExitCode(0);
    }

    public function test_notification_status_supports_json_output(): void
    {
        $exitCode = Artisan::call('notification:status', ['--json' => true]);

        $this->assertSame(0, $exitCode);

        $output = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame([
            'pending_deliveries' => 0,
            'failed_deliveries' => 0,
            'sent_deliveries' => 0,
            'oldest_pending_age_seconds' => 0,
        ], $output);
    }
}
