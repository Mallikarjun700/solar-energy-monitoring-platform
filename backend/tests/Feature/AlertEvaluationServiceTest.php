<?php

namespace Tests\Feature;

use App\Enums\AlertOperator;
use App\Enums\AlertSeverity;
use App\Models\AlertRule;
use App\Services\AlertEvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertEvaluationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_greater_than_rule_is_triggered(): void
    {
        $rule = AlertRule::factory()->create([
            'metric' => 'temperature',
            'operator' => AlertOperator::GREATER_THAN,
            'threshold' => 80,
            'severity' => AlertSeverity::CRITICAL,
        ]);

        $result = app(AlertEvaluationService::class)->evaluate(
            [
                'payload' => [
                    'temperature' => 85,
                ],
            ],
            $rule
        );

        $this->assertTrue($result);
    }

    public function test_rule_is_not_triggered_when_value_is_normal(): void
    {
        $rule = AlertRule::factory()->create([
            'metric' => 'temperature',
            'operator' => AlertOperator::GREATER_THAN,
            'threshold' => 80,
        ]);

        $result = app(AlertEvaluationService::class)->evaluate(
            [
                'payload' => [
                    'temperature' => 75,
                ],
            ],
            $rule
        );

        $this->assertFalse($result);
    }

    public function test_greater_than_or_equal_operator(): void
    {
        $rule = AlertRule::factory()->create([
            'metric' => 'temperature',
            'operator' => AlertOperator::GREATER_THAN_OR_EQUAL,
            'threshold' => 80,
        ]);

        $this->assertTrue(
            app(AlertEvaluationService::class)->evaluate(
                ['temperature' => 80],
                $rule
            )
        );
    }

    public function test_less_than_operator(): void
    {
        $rule = AlertRule::factory()->create([
            'metric' => 'voltage',
            'operator' => AlertOperator::LESS_THAN,
            'threshold' => 200,
        ]);

        $this->assertTrue(
            app(AlertEvaluationService::class)->evaluate(
                ['voltage' => 190],
                $rule
            )
        );
    }

    public function test_less_than_or_equal_operator(): void
    {
        $rule = AlertRule::factory()->create([
            'metric' => 'voltage',
            'operator' => AlertOperator::LESS_THAN_OR_EQUAL,
            'threshold' => 200,
        ]);

        $this->assertTrue(
            app(AlertEvaluationService::class)->evaluate(
                ['voltage' => 200],
                $rule
            )
        );
    }

    public function test_equal_operator(): void
    {
        $rule = AlertRule::factory()->create([
            'metric' => 'power_kw',
            'operator' => AlertOperator::EQUAL,
            'threshold' => 50,
        ]);

        $this->assertTrue(
            app(AlertEvaluationService::class)->evaluate(
                ['power_kw' => 50],
                $rule
            )
        );
    }

    public function test_missing_metric_does_not_trigger_alert(): void
    {
        $rule = AlertRule::factory()->create([
            'metric' => 'temperature',
            'operator' => AlertOperator::GREATER_THAN,
            'threshold' => 80,
        ]);

        $this->assertFalse(
            app(AlertEvaluationService::class)->evaluate(
                ['power_kw' => 50],
                $rule
            )
        );
    }

    public function test_disabled_rule_does_not_trigger_alert(): void
    {
        $rule = AlertRule::factory()->create([
            'enabled' => false,
            'metric' => 'temperature',
            'operator' => AlertOperator::GREATER_THAN,
            'threshold' => 80,
        ]);

        $this->assertFalse(
            app(AlertEvaluationService::class)->evaluate(
                ['temperature' => 90],
                $rule
            )
        );
    }
}
