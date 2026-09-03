<?php

namespace Tests\Feature;

use App\Enums\AlertOperator;
use App\Events\AlertCreated;
use App\Listeners\HandleAlertCreated;
use App\Models\AlertRule;
use App\Services\AlertCreationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class AlertCreatedEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_alert_creation_dispatches_alert_created_event(): void
    {
        Event::fake([
            AlertCreated::class,
        ]);

        $tenantId = (string) Str::uuid();

        $rule = AlertRule::factory()->create([
            'tenant_id' => $tenantId,
            'metric' => 'temperature',
            'operator' => AlertOperator::GREATER_THAN,
            'threshold' => 80,
            'enabled' => true,
        ]);

        $alert = app(AlertCreationService::class)
            ->evaluateAndCreate([
                'event_id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'source_id' => (string) Str::uuid(),
                'payload' => [
                    'device_id' => 100,
                    'temperature' => 90,
                ],
            ], $rule);

        $this->assertNotNull($alert);

        Event::assertDispatched(
            AlertCreated::class,
            function (AlertCreated $event) use ($alert) {
                return $event->alert->id === $alert->id;
            }
        );
    }

    public function test_normal_telemetry_does_not_dispatch_alert_created(): void
    {
        Event::fake([
            AlertCreated::class,
        ]);

        $tenantId = (string) Str::uuid();

        $rule = AlertRule::factory()->create([
            'tenant_id' => $tenantId,
            'metric' => 'temperature',
            'operator' => AlertOperator::GREATER_THAN,
            'threshold' => 80,
            'enabled' => true,
        ]);

        $alert = app(AlertCreationService::class)
            ->evaluateAndCreate([
                'event_id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'source_id' => (string) Str::uuid(),
                'payload' => [
                    'device_id' => 100,
                    'temperature' => 70,
                ],
            ], $rule);

        $this->assertNull($alert);

        Event::assertNotDispatched(AlertCreated::class);
    }

    public function test_alert_created_listener_is_queueable(): void
    {
        $listener = new HandleAlertCreated;

        $this->assertInstanceOf(
            ShouldQueue::class,
            $listener
        );

        $this->assertSame(3, $listener->tries);
        $this->assertSame(30, $listener->timeout);
        $this->assertSame([10, 30, 60], $listener->backoff);
    }
}
