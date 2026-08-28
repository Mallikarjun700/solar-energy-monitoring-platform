<?php

namespace App\Services;

use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\AlertRule;
use Illuminate\Support\Facades\DB;
use Throwable;
use App\Events\AlertCreated;

class AlertCreationService
{
    public function __construct(
        private AlertEvaluationService $evaluationService
    ) {
    }

    /**
     * Evaluate telemetry and create an alert when the rule is violated.
     *
     * @param array<string, mixed> $telemetry
     */
    public function evaluateAndCreate(
        array $telemetry,
        AlertRule $rule
    ): ?Alert {
        if (! $this->evaluationService->evaluate(
            $telemetry,
            $rule
        )) {
            return null;
        }

        $tenantId = $telemetry['tenant_id'] ?? $rule->tenant_id;
        $deviceId = $telemetry['device_id']
            ?? $telemetry['payload']['device_id']
            ?? null;
        if ($deviceId === null) {
            return null;
        }

        /*
         * First check for an existing active alert.
         */
        $existing = Alert::query()
            ->where('tenant_id', $tenantId)
            ->where('device_id', $deviceId)
            ->where('rule_id', $rule->id)
            ->whereIn('status', [
                AlertStatus::OPEN,
                AlertStatus::ACKNOWLEDGED,
            ])
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        try {
            return DB::transaction(function () use (
                $telemetry,
                $rule,
                $tenantId,
                $deviceId
            ) {
                /*
                 * Re-check inside the transaction because another
                 * worker may have created the alert after our first
                 * lookup.
                 */
                $existing = Alert::query()
                    ->where('tenant_id', $tenantId)
                    ->where('device_id', $deviceId)
                    ->where('rule_id', $rule->id)
                    ->whereIn('status', [
                        AlertStatus::OPEN,
                        AlertStatus::ACKNOWLEDGED,
                    ])
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return $existing;
                }
                $alert = Alert::create([
                    'tenant_id' => $tenantId,
                    'plant_id' => $telemetry['plant_id'] ?? null,
                    'asset_id' => $telemetry['asset_id'] ?? null,
                    'device_id' => $deviceId,
                    'rule_id' => $rule->id,
                    'event_id' => $telemetry['event_id'] ?? null,
                    'alert_type' => $rule->alert_type,
                    'severity' => $rule->severity,
                    'status' => AlertStatus::OPEN,
                    'message' => $this->buildMessage(
                        $rule,
                        $telemetry
                    ),
                    'triggered_at' => now(),
                ]);

                AlertCreated::dispatch($alert);

                return $alert;
                
            });
        } catch (Throwable $exception) {
            /*
             * The database unique index is the final protection
             * against duplicate active alerts.
             *
             * If another worker won the race, return that alert.
             */
            $existing = Alert::query()
                ->where('tenant_id', $tenantId)
                ->where('device_id', $deviceId)
                ->where('rule_id', $rule->id)
                ->whereIn('status', [
                    AlertStatus::OPEN,
                    AlertStatus::ACKNOWLEDGED,
                ])
                ->latest('id')
                ->first();

            if ($existing) {
                return $existing;
            }

            throw $exception;
        }
    }

    private function buildMessage(AlertRule $rule,array $telemetry): string {
        $value = $telemetry[$rule->metric]
            ?? $telemetry['payload'][$rule->metric]
            ?? null;

        return sprintf(
            '%s: %s=%s threshold=%s',
            $rule->name,
            $rule->metric,
            (string) $value,
            (string) $rule->threshold
        );
    }

    public function evaluateAndResolve(array $telemetry,AlertRule $rule): ?Alert {
        if (! $rule->enabled) {
            return null;
        }

        $metric = $rule->metric;

        $valueExists = array_key_exists($metric, $telemetry)
            || (
                isset($telemetry['payload'])
                && is_array($telemetry['payload'])
                && array_key_exists($metric, $telemetry['payload'])
            );

        /*
        * Missing telemetry metric is not the same as
        * a healthy/normal telemetry value.
        */
        if (! $valueExists) {
            return null;
        }

        $violated = $this->evaluationService->evaluate(
            $telemetry,
            $rule
        );

        /*
        * Rule is still violated.
        *
        * AlertCreationService handles creation/deduplication.
        */
        if ($violated) {
            return $this->evaluateAndCreate(
                $telemetry,
                $rule
            );
        }

        $tenantId = $telemetry['tenant_id'] ?? $rule->tenant_id;

        $deviceId = $telemetry['device_id']
            ?? $telemetry['payload']['device_id']
            ?? null;

        if ($deviceId === null) {
            return null;
        }

        $activeAlert = Alert::query()
            ->where('tenant_id', $tenantId)
            ->where('device_id', $deviceId)
            ->where('rule_id', $rule->id)
            ->whereIn('status', [
                AlertStatus::OPEN,
                AlertStatus::ACKNOWLEDGED,
            ])
            ->latest('id')
            ->first();

        if (! $activeAlert) {
            return null;
        }

        $activeAlert->update([
            'status' => AlertStatus::RESOLVED,
            'resolved_at' => now(),
        ]);

        return $activeAlert->fresh();
    }
}