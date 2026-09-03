<?php

namespace App\Services;

use App\Enums\AlertOperator;
use App\Models\AlertRule;

class AlertEvaluationService
{
    /**
     * Evaluate a telemetry payload against an alert rule.
     *
     * @param  array<string, mixed>  $telemetry
     */
    public function evaluate(
        array $telemetry,
        AlertRule $rule
    ): bool {
        if (! $rule->enabled) {
            return false;
        }

        $value = $this->extractMetricValue(
            $telemetry,
            $rule->metric
        );

        if ($value === null) {
            return false;
        }

        return $this->compare(
            (float) $value,
            $rule->operator,
            (float) $rule->threshold
        );
    }

    /**
     * Extract a metric from the telemetry payload.
     */
    private function extractMetricValue(
        array $telemetry,
        string $metric
    ): mixed {
        /*
         * Telemetry events store measurements inside
         * the payload object.
         */
        if (array_key_exists($metric, $telemetry)) {
            return $telemetry[$metric];
        }

        if (
            isset($telemetry['payload'])
            && is_array($telemetry['payload'])
            && array_key_exists(
                $metric,
                $telemetry['payload']
            )
        ) {
            return $telemetry['payload'][$metric];
        }

        return null;
    }

    private function compare(
        float $value,
        AlertOperator $operator,
        float $threshold
    ): bool {
        return match ($operator) {
            AlertOperator::GREATER_THAN => $value > $threshold,

            AlertOperator::GREATER_THAN_OR_EQUAL => $value >= $threshold,

            AlertOperator::LESS_THAN => $value < $threshold,

            AlertOperator::LESS_THAN_OR_EQUAL => $value <= $threshold,

            AlertOperator::EQUAL => $value === $threshold,
        };
    }
}
