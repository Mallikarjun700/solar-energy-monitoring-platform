<?php

namespace Tests\Feature;

use App\Enums\AlertOperator;
use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\AlertRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_alert_rule_casts_enum_values(): void
    {
        $rule = AlertRule::factory()->create();

        $this->assertInstanceOf(
            AlertOperator::class,
            $rule->operator
        );

        $this->assertInstanceOf(
            AlertSeverity::class,
            $rule->severity
        );

        $this->assertTrue($rule->enabled);
    }

    public function test_alert_casts_enum_values(): void
    {
        $alert = Alert::factory()->create();

        $this->assertInstanceOf(
            AlertSeverity::class,
            $alert->severity
        );

        $this->assertInstanceOf(
            AlertStatus::class,
            $alert->status
        );
    }

    public function test_alert_belongs_to_rule(): void
    {
        $rule = AlertRule::factory()->create();

        $alert = Alert::factory()->create([
            'rule_id' => $rule->id,
            'tenant_id' => $rule->tenant_id,
        ]);

        $this->assertTrue(
            $alert->rule->is($rule)
        );
    }

    public function test_rule_has_alerts(): void
    {
        $rule = AlertRule::factory()->create();

        Alert::factory()->create([
            'rule_id' => $rule->id,
            'tenant_id' => $rule->tenant_id,
        ]);

        $this->assertCount(
            1,
            $rule->alerts
        );
    }
}
