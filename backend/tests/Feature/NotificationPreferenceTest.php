<?php

namespace Tests\Feature;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\NotificationPreference;
use App\Services\Notifications\AlertNotificationService;
use App\Services\Notifications\NotificationPreferenceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_preferences_are_resolved_by_tenant(): void
    {
        $tenantId = '550e8400-e29b-41d4-a716-446655440001';

        NotificationPreference::create([
            'tenant_id' => $tenantId,
            'enabled' => true,
            'channel' => 'email',
        ]);

        $alert = Alert::factory()->create([
            'tenant_id' => $tenantId,
        ]);

        $preference = app(
            NotificationPreferenceResolver::class
        )->resolve($alert);

        $this->assertNotNull($preference);
        $this->assertSame('email', $preference->channel);
        $this->assertTrue($preference->enabled);
    }

    public function test_missing_preferences_return_null(): void
    {
        $alert = Alert::factory()->create();

        $preference = app(
            NotificationPreferenceResolver::class
        )->resolve($alert);

        $this->assertNull($preference);
    }

    public function test_disabled_preferences_are_not_enabled(): void
    {
        $preference = NotificationPreference::create([
            'tenant_id' => '550e8400-e29b-41d4-a716-446655440001',
            'enabled' => false,
            'channel' => 'email',
        ]);

        $this->assertFalse(
            $preference->isEnabledForSeverity('critical')
        );
    }

    public function test_empty_severity_list_allows_all_severities(): void
    {
        $preference = NotificationPreference::create([
            'tenant_id' => '550e8400-e29b-41d4-a716-446655440001',
            'enabled' => true,
            'channel' => 'email',
            'severity_levels' => null,
        ]);

        $this->assertTrue(
            $preference->isEnabledForSeverity('info')
        );

        $this->assertTrue(
            $preference->isEnabledForSeverity('critical')
        );
    }

    public function test_severity_filter_is_respected(): void
    {
        $preference = NotificationPreference::create([
            'tenant_id' => '550e8400-e29b-41d4-a716-446655440001',
            'enabled' => true,
            'channel' => 'email',
            'severity_levels' => [
                'critical',
                'emergency',
            ],
        ]);

        $this->assertTrue(
            $preference->isEnabledForSeverity('critical')
        );

        $this->assertTrue(
            $preference->isEnabledForSeverity('emergency')
        );

        $this->assertFalse(
            $preference->isEnabledForSeverity('warning')
        );
    }

    public function test_tenant_email_preference_is_used(): void
    {
        config([
            'services.notifications.channel' => 'email',
            'services.notifications.email' => 'global@example.com',
        ]);

        \Illuminate\Support\Facades\Mail::fake();

        $tenantId = '550e8400-e29b-41d4-a716-446655440001';

        NotificationPreference::create([
            'tenant_id' => $tenantId,
            'enabled' => true,
            'channel' => 'email',
            'email' => 'tenant@example.com',
        ]);

        $alert = Alert::factory()->create(['tenant_id' => $tenantId,]);

        app(AlertNotificationService::class)->send($alert);

        \Illuminate\Support\Facades\Mail::assertSent(
            \App\Mail\AlertNotificationMail::class,
            function ($mail) {
                return $mail->hasTo('tenant@example.com');
            }
        );
    }

    public function test_tenant_webhook_preference_is_used(): void
    {
        config(['services.notifications.channel' => 'log',]);

        \Illuminate\Support\Facades\Http::fake([
            'https://tenant.example.com/alerts' =>
                \Illuminate\Support\Facades\Http::response(
                    ['ok' => true],
                    200
                ),
        ]);

        $tenantId = '550e8400-e29b-41d4-a716-446655440002';

        NotificationPreference::create([
            'tenant_id' => $tenantId,
            'enabled' => true,
            'channel' => 'webhook',
            'webhook_url' => 'https://tenant.example.com/alerts',
            'webhook_secret' => 'tenant-secret',
        ]);

        $alert = Alert::factory()->create(['tenant_id' => $tenantId,]);

        app(AlertNotificationService::class)->send($alert);

        \Illuminate\Support\Facades\Http::assertSent(
            function ($request) {
                return $request->url() ===
                    'https://tenant.example.com/alerts';
            }
        );
    }
}
