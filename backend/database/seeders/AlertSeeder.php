<?php

namespace Database\Seeders;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\AlertOperator;
use App\Enums\NotificationDeliveryStatus;
use App\Models\Alert;
use App\Models\AlertRule;
use App\Models\Device;
use App\Models\NotificationPreference;
use Illuminate\Database\Seeder;

class AlertSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = '11111111-1111-4111-8111-111111111111';
        $deviceId = Device::query()->orderBy('id')->value('id');

        if ($deviceId === null) {
            return;
        }

        $rule = AlertRule::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'name' => 'High Temperature',
            ],
            [
                'metric' => 'temperature',
                'operator' => AlertOperator::GREATER_THAN->value,
                'threshold' => 80,
                'severity' => AlertSeverity::CRITICAL->value,
                'alert_type' => 'HIGH_TEMPERATURE',
                'enabled' => true,
            ]
        );

        $alert = Alert::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'device_id' => $deviceId,
                'rule_id' => $rule->id,
                'alert_type' => 'HIGH_TEMPERATURE',
            ],
            [
                'severity' => AlertSeverity::CRITICAL->value,
                'status' => AlertStatus::OPEN->value,
                'message' => 'Temperature exceeded configured threshold.',
                'triggered_at' => now()->subMinutes(5),
                'acknowledged_at' => null,
                'resolved_at' => null,
            ]
        );

        NotificationPreference::updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'enabled' => true,
                'channel' => 'log',
                'email' => 'alerts@example.com',
                'severity_levels' => [
                    AlertSeverity::WARNING->value,
                    AlertSeverity::CRITICAL->value,
                ],
            ]
        );

        $alert->notificationDeliveries()->updateOrCreate(
            ['channel' => 'log'],
            [
                'status' => NotificationDeliveryStatus::PENDING->value,
                'attempts' => 0,
                'first_attempted_at' => null,
                'last_attempted_at' => null,
                'delivered_at' => null,
                'last_error' => null,
            ]
        );
    }
}
