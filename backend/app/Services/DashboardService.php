<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Device;
use App\Models\Plant;
use App\Models\Telemetry;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getDashboard(string $tenantId): array
    {
        $now = now();
        $startOfToday = $now->copy()->startOfDay();

        $latestTelemetry = $this->latestTelemetryForDevices($tenantId);

        return [
            'kpis' => $this->getKpis($tenantId, $latestTelemetry, $startOfToday),
            'plants' => $this->getPlants($tenantId, $latestTelemetry),
            'devices' => $this->getDevices($tenantId, $latestTelemetry),
            'energyTrend' => $this->getEnergyTrend($tenantId, $startOfToday),
            'alerts' => $this->getAlerts($tenantId),
            'recentTelemetry' => $this->getRecentTelemetry($tenantId),
            'recentActivity' => $this->getRecentActivity($tenantId),
        ];
    }

    private function getKpis(
        string $tenantId,
        Collection $latestTelemetry,
        Carbon $startOfToday
    ): array {
        $totalPlants = Plant::query()
            ->where('tenant_id', $tenantId)
            ->count();

        $totalDevices = Device::query()
            ->where('tenant_id', $tenantId)
            ->count();

        $activeDevices = Device::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['ONLINE', 'ACTIVE', 'online', 'active'])
            ->count();

        $currentPowerKw = $latestTelemetry->sum(
            fn (Telemetry $telemetry): float => (float) ($telemetry->power ?? 0)
        );

        $todayEnergyKwh = Telemetry::query()
            ->where('tenant_id', $tenantId)
            ->where('recorded_at', '>=', $startOfToday)
            ->sum('energy_generated');

        return [
            'totalPlants' => $totalPlants,
            'totalDevices' => $totalDevices,
            'activeDevices' => $activeDevices,
            'currentPowerKw' => $latestTelemetry->isEmpty()
                ? null
                : round($currentPowerKw, 2),
            'todayEnergyKwh' => $todayEnergyKwh === null
                ? null
                : round((float) $todayEnergyKwh, 4),
        ];
    }

    private function getPlants(
        string $tenantId,
        Collection $latestTelemetry
    ): array {
        $latestByDevice = $latestTelemetry->keyBy('device_id');

        $devicesByPlant = Device::query()
            ->where('tenant_id', $tenantId)
            ->select([
                'devices.id',
                'devices.asset_id',
            ])
            ->with('asset:id,plant_id,tenant_id')
            ->get()
            ->groupBy(fn (Device $device) => $device->asset?->plant_id);

        return Plant::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->get()
            ->map(function (Plant $plant) use (
                $devicesByPlant,
                $latestByDevice
            ): array {
                $devices = $devicesByPlant->get($plant->id, collect());

                $power = 0.0;
                $hasTelemetry = false;

                foreach ($devices as $device) {
                    $telemetry = $latestByDevice->get($device->id);

                    if ($telemetry === null) {
                        continue;
                    }

                    $hasTelemetry = true;
                    $power += (float) ($telemetry->power ?? 0);
                }

                $capacity = $plant->capacity_kw !== null
                    ? (float) $plant->capacity_kw
                    : null;

                return [
                    'id' => $plant->id,
                    'name' => $plant->name,
                    'code' => $plant->code,
                    'location' => $plant->location,
                    'capacityKw' => $capacity,
                    'status' => $plant->status,
                    'currentPowerKw' => $hasTelemetry
                        ? round($power, 2)
                        : null,
                    'performancePercent' =>
                        $capacity !== null
                        && $capacity > 0
                        && $hasTelemetry
                            ? round(($power / $capacity) * 100, 2)
                            : null,
                ];
            })
            ->all();
    }

    private function getDevices(
        string $tenantId,
        Collection $latestTelemetry
    ): array {
        $latestByDevice = $latestTelemetry->keyBy('device_id');

        return Device::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->get()
            ->map(function (Device $device) use ($latestByDevice): array {
                /** @var Telemetry|null $telemetry */
                $telemetry = $latestByDevice->get($device->id);

                return [
                    'id' => $device->id,
                    'assetId' => $device->asset_id,
                    'deviceType' => $device->device_type,
                    'serialNumber' => $device->serial_number,
                    'status' => $device->status,
                    'lastSeenAt' => $device->last_seen_at?->toISOString(),
                    'currentPowerKw' => $telemetry?->power !== null
                        ? (float) $telemetry->power
                        : null,
                    'temperature' => $telemetry?->temperature !== null
                        ? (float) $telemetry->temperature
                        : null,
                    'voltage' => $telemetry?->voltage !== null
                        ? (float) $telemetry->voltage
                        : null,
                    'current' => $telemetry?->current !== null
                        ? (float) $telemetry->current
                        : null,
                    'telemetryTimestamp' =>
                        $telemetry?->recorded_at?->toISOString(),
                ];
            })
            ->all();
    }

    private function getEnergyTrend(
        string $tenantId,
        Carbon $startOfToday
    ): array {
        $query = Telemetry::query()
            ->where('tenant_id', $tenantId)
            ->where('recorded_at', '>=', $startOfToday);

        /*
         * Telemetry uses the application's telemetry connection.
         * Keep the aggregation SQL compatible with both MySQL and
         * SQLite because PHPUnit intentionally uses SQLite.
         */
        $driver = $query->getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $query
                ->selectRaw(
                    "DATE_FORMAT(recorded_at, '%Y-%m-%d %H:00:00') as bucket"
                )
                ->selectRaw('SUM(energy_generated) as energy_kwh');
        } elseif ($driver === 'sqlite') {
            $query
                ->selectRaw(
                    "strftime('%Y-%m-%d %H:00:00', recorded_at) as bucket"
                )
                ->selectRaw('SUM(energy_generated) as energy_kwh');
        } else {
            throw new \RuntimeException(
                sprintf(
                    'Unsupported telemetry database driver: %s',
                    $driver
                )
            );
        }

        return $query
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->map(fn ($row): array => [
                'timestamp' => Carbon::parse(
                    $row->bucket,
                    'UTC'
                )->toISOString(),
                'energyKwh' => $row->energy_kwh !== null
                    ? round((float) $row->energy_kwh, 4)
                    : null,
            ])
            ->all();
    }

    private function getAlerts(string $tenantId): array
    {
        return Alert::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['open', 'acknowledged'])
            ->orderByDesc('triggered_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn (Alert $alert): array => [
                'id' => $alert->id,
                'tenant_id' => $alert->tenant_id,
                'device_id' => $alert->device_id,
                'rule_id' => $alert->rule_id,
                'alert_type' => $alert->alert_type,
                'severity' => $alert->severity?->value
                    ?? (string) $alert->severity,
                'status' => $alert->status?->value
                    ?? (string) $alert->status,
                'message' => $alert->message,
                'triggered_at' => $alert->triggered_at?->toISOString(),
                'acknowledged_at' =>
                    $alert->acknowledged_at?->toISOString(),
                'resolved_at' => $alert->resolved_at?->toISOString(),
                'created_at' => $alert->created_at?->toISOString(),
                'updated_at' => $alert->updated_at?->toISOString(),
            ])
            ->all();
    }

    private function getRecentTelemetry(string $tenantId): array
    {
        return Telemetry::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn (Telemetry $telemetry): array => [
                'eventId' => (string) $telemetry->id,
                'eventType' => 'telemetry',
                'timestamp' => $telemetry->recorded_at?->toISOString(),
                'powerKw' => $telemetry->power !== null
                    ? (float) $telemetry->power
                    : null,
                'energyKwh' => $telemetry->energy_generated !== null
                    ? (float) $telemetry->energy_generated
                    : null,
                'deviceId' => $telemetry->device_id,
            ])
            ->all();
    }

    private function getRecentActivity(string $tenantId): array
    {
        $telemetryActivities = Telemetry::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn (Telemetry $telemetry): array => [
                'id' => $telemetry->id,
                'type' => 'telemetry',
                'message' => sprintf(
                    'Telemetry received from device %d.',
                    $telemetry->device_id
                ),
                'timestamp' => $telemetry->recorded_at?->toISOString(),
                'deviceId' => $telemetry->device_id,
                'eventId' => (string) $telemetry->id,
            ]);

        $alertActivities = Alert::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('triggered_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn (Alert $alert): array => [
                'id' => 'alert-' . $alert->id,
                'type' => 'alert',
                'message' => $alert->message,
                'timestamp' => $alert->triggered_at?->toISOString(),
                'deviceId' => $alert->device_id,
                'eventId' => $alert->event_id?->toString(),
            ]);

        return $telemetryActivities
            ->concat($alertActivities)
            ->sortByDesc('timestamp')
            ->take(10)
            ->values()
            ->all();
    }

    private function latestTelemetryForDevices(string $tenantId): Collection
    {
        $latestIds = Telemetry::query()
            ->where('tenant_id', $tenantId)
            ->selectRaw('MAX(id) as id')
            ->groupBy('device_id');

        return Telemetry::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $latestIds)
            ->get();
    }
}
