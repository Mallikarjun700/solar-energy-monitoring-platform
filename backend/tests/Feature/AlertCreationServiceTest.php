<?php

namespace Tests\Feature;

use App\Enums\AlertOperator;
use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\AlertRule;
use App\Services\AlertCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AlertCreationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_violation_creates_alert(): void
    {
        $tenantId = (string) Str::uuid();

        $rule = AlertRule::factory()->create([
            'tenant_id' => $tenantId,
            'metric' => 'temperature',
            'operator' => AlertOperator::GREATER_THAN,
            'threshold' => 80,
            'severity' => AlertSeverity::CRITICAL,
            'alert_type' => 'HIGH_TEMPERATURE',
        ]);

        $eventId = (string) Str::uuid();

        $alert = app(AlertCreationService::class)
            ->evaluateAndCreate([
                'event_id' => $eventId,
                'tenant_id' => $tenantId,
                'payload' => [
                    'device_id' => 100,
                    'temperature' => 85,
                ],
            ], $rule);

        $this->assertNotNull($alert);

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'tenant_id' => $tenantId,
            'device_id' => 100,
            'rule_id' => $rule->id,
            'event_id' => $eventId,
            'status' => AlertStatus::OPEN->value,
        ]);
    }

    public function test_normal_telemetry_does_not_create_alert(): void
    {
        $rule = AlertRule::factory()->create([
            'metric' => 'temperature',
            'operator' => AlertOperator::GREATER_THAN,
            'threshold' => 80,
        ]);

        $result = app(AlertCreationService::class)
            ->evaluateAndCreate([
                'tenant_id' => $rule->tenant_id,
                'payload' => [
                    'device_id' => 100,
                    'temperature' => 70,
                ],
            ], $rule);

        $this->assertNull($result);

        $this->assertDatabaseCount('alerts', 0);
    }

    public function test_repeated_violation_reuses_existing_active_alert(): void
    {
        $tenantId = (string) Str::uuid();

        $rule = AlertRule::factory()->create([
            'tenant_id' => $tenantId,
            'metric' => 'temperature',
            'operator' => AlertOperator::GREATER_THAN,
            'threshold' => 80,
        ]);

        $first = app(AlertCreationService::class)
            ->evaluateAndCreate([
                'event_id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'payload' => [
                    'device_id' => 100,
                    'temperature' => 85,
                ],
            ], $rule);

        $second = app(AlertCreationService::class)
            ->evaluateAndCreate([
                'event_id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'payload' => [
                    'device_id' => 100,
                    'temperature' => 90,
                ],
            ], $rule);

        $this->assertNotNull($first);
        $this->assertNotNull($second);

        $this->assertSame(
            $first->id,
            $second->id
        );

        $this->assertDatabaseCount('alerts', 1);
    }

    public function test_resolved_alert_allows_new_alert(): void
    {
        $tenantId = (string) Str::uuid();

        $rule = AlertRule::factory()->create([
            'tenant_id' => $tenantId,
            'metric' => 'temperature',
            'operator' => AlertOperator::GREATER_THAN,
            'threshold' => 80,
        ]);

        $first = app(AlertCreationService::class)
            ->evaluateAndCreate([
                'event_id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'payload' => [
                    'device_id' => 100,
                    'temperature' => 85,
                ],
            ], $rule);

        $first->update([
            'status' => AlertStatus::RESOLVED,
            'resolved_at' => now(),
        ]);

        $second = app(AlertCreationService::class)
            ->evaluateAndCreate([
                'event_id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'payload' => [
                    'device_id' => 100,
                    'temperature' => 90,
                ],
            ], $rule);

        $this->assertNotNull($second);

        $this->assertNotSame(
            $first->id,
            $second->id
        );

        $this->assertDatabaseCount('alerts', 2);
    }

    public function test_different_devices_can_have_separate_alerts(): void
    {
        $tenantId = (string) Str::uuid();

        $rule = AlertRule::factory()->create([
            'tenant_id' => $tenantId,
            'metric' => 'temperature',
            'operator' => AlertOperator::GREATER_THAN,
            'threshold' => 80,
        ]);

        $first = app(AlertCreationService::class)
            ->evaluateAndCreate([
                'tenant_id' => $tenantId,
                'payload' => [
                    'device_id' => 100,
                    'temperature' => 85,
                ],
            ], $rule);

        $second = app(AlertCreationService::class)
            ->evaluateAndCreate([
                'tenant_id' => $tenantId,
                'payload' => [
                    'device_id' => 101,
                    'temperature' => 85,
                ],
            ], $rule);

        $this->assertNotNull($first);
        $this->assertNotNull($second);

        $this->assertNotSame(
            $first->id,
            $second->id
        );

        $this->assertDatabaseCount('alerts', 2);
    }

    public function test_normal_telemetry_automatically_resolves_open_alert(): void
    {
        $tenantId = (string) Str::uuid();

        $rule = AlertRule::factory()->create([
            'tenant_id' => $tenantId,
            'metric' => 'temperature',
            'operator' => AlertOperator::GREATER_THAN,
            'threshold' => 80,
            'enabled' => true,
        ]);

        $alert = Alert::factory()->create([
            'tenant_id' => $tenantId,
            'device_id' => 100,
            'rule_id' => $rule->id,
            'status' => AlertStatus::OPEN,
            'resolved_at' => null,
        ]);

        $result = app(AlertCreationService::class)
            ->evaluateAndResolve([
                'event_id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'source_id' => (string) Str::uuid(),
                'payload' => [
                    'device_id' => 100,
                    'temperature' => 70,
                ],
            ], $rule);

        $this->assertNotNull($result);

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => AlertStatus::RESOLVED->value,
        ]);

        $this->assertNotNull(
            $alert->fresh()->resolved_at
        );
    }

    public function test_normal_telemetry_automatically_resolves_acknowledged_alert(): void
    {
        $tenantId = (string) Str::uuid();

        $rule = AlertRule::factory()->create([
            'tenant_id' => $tenantId,
            'metric' => 'temperature',
            'operator' => AlertOperator::GREATER_THAN,
            'threshold' => 80,
            'enabled' => true,
        ]);

        $alert = Alert::factory()->create([
            'tenant_id' => $tenantId,
            'device_id' => 100,
            'rule_id' => $rule->id,
            'status' => AlertStatus::ACKNOWLEDGED,
            'acknowledged_at' => now(),
            'resolved_at' => null,
        ]);

        $result = app(AlertCreationService::class)
            ->evaluateAndResolve([
                'event_id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'source_id' => (string) Str::uuid(),
                'payload' => [
                    'device_id' => 100,
                    'temperature' => 70,
                ],
            ], $rule);

        $this->assertNotNull($result);

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => AlertStatus::RESOLVED->value,
        ]);
    }

    public function test_missing_metric_does_not_resolve_active_alert(): void
    {
        $tenantId = (string) Str::uuid();

        $rule = AlertRule::factory()->create([
            'tenant_id' => $tenantId,
            'metric' => 'temperature',
            'operator' => AlertOperator::GREATER_THAN,
            'threshold' => 80,
            'enabled' => true,
        ]);

        $alert = Alert::factory()->create([
            'tenant_id' => $tenantId,
            'device_id' => 100,
            'rule_id' => $rule->id,
            'status' => AlertStatus::OPEN,
            'resolved_at' => null,
        ]);

        $result = app(AlertCreationService::class)
            ->evaluateAndResolve([
                'event_id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'source_id' => (string) Str::uuid(),
                'payload' => [
                    'device_id' => 100,
                ],
            ], $rule);

        $this->assertNull($result);

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'status' => AlertStatus::OPEN->value,
        ]);
    }
}