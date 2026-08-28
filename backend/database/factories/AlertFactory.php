<?php

namespace Database\Factories;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\AlertRule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Alert>
 */
class AlertFactory extends Factory
{
    protected $model = Alert::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => (string) Str::uuid(),
            'plant_id' => null,
            'asset_id' => null,
            'device_id' => null,
            'rule_id' => AlertRule::factory(),
            'event_id' => null,
            'alert_type' => 'HIGH_TEMPERATURE',
            'severity' => AlertSeverity::CRITICAL,
            'status' => AlertStatus::OPEN,
            'message' => 'Temperature exceeded configured threshold.',
            'triggered_at' => now(),
            'acknowledged_at' => null,
            'resolved_at' => null,
        ];
    }
}
