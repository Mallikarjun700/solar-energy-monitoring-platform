<?php

namespace App\Services;

use App\Models\Telemetry;
use App\Models\TelemetryEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use App\Models\AlertRule;
use App\Services\AlertCreationService;
use App\Jobs\EvaluateTelemetryAlertsJob;

class TelemetryService
{
    public function ingest(array $events): array
    {
        $accepted = 0;
        $duplicates = 0;

        $rows = [];

        foreach ($events as $event) {
            $rows[] = [
                'event_id' => $event['event_id'],
                'tenant_id' => $event['tenant_id'],
                'source_id' => $event['source_id'],
                'event_type' => $event['event_type'],
                'event_timestamp' => $event['timestamp'],
                'received_at' => now(),
                'schema_version' => $event['schema_version'],
                'attributes' => isset($event['attributes'])
                    ? json_encode($event['attributes'])
                    : null,
                'payload' => isset($event['payload'])
                    ? json_encode($event['payload'])
                    : null,
                'created_at' => now(),
            ];
        }

        if (empty($rows)) {
            return [
                'accepted' => 0,
                'duplicates' => 0,
                'rejected' => 0,
            ];
        }

        $inserted = DB::transaction(function () use ($rows) {
            return TelemetryEvent::query()->insertOrIgnore($rows);
        });

        $accepted = $inserted;
        $duplicates = count($rows) - $accepted;

        /*
        * Only evaluate telemetry that now exists in the database.
        *
        * This prevents duplicate telemetry from repeatedly triggering
        * alert evaluation.
        */
        if ($accepted > 0) {
            $eventIds = array_column($rows, 'event_id');

            $storedEvents = TelemetryEvent::query()
                ->whereIn('event_id', $eventIds)
                ->get();

            $rulesByTenant = AlertRule::query()
                ->whereIn(
                    'tenant_id',
                    $storedEvents->pluck('tenant_id')->unique()->values()
                )
                ->where('enabled', true)
                ->get()
                ->groupBy('tenant_id');

            $alertCreationService = app(AlertCreationService::class);

            foreach ($storedEvents as $storedEvent) {
                EvaluateTelemetryAlertsJob::dispatch([
                    'event_id' => $storedEvent->event_id,
                    'tenant_id' => $storedEvent->tenant_id,
                    'source_id' => $storedEvent->source_id,
                    'event_type' => $storedEvent->event_type,
                    'timestamp' => $storedEvent->event_timestamp,
                    'attributes' => $storedEvent->attributes,
                    'payload' => $storedEvent->payload,
                ]);
            }
        }

        return [
            'accepted' => $accepted,
            'duplicates' => $duplicates,
            'rejected' => 0,
        ];
    }

    public function process(array $payload): void
    {
        if (empty($payload)) {
            throw new InvalidArgumentException('Telemetry payload is empty.');
        }

        $deviceId = $payload['device_id'] ?? null;
        if ($deviceId === null) {
            throw new InvalidArgumentException('Telemetry payload must contain device_id.');
        }

        $recordedAt = $payload['recorded_at'] ?? $payload['timestamp'] ?? now();
        $recordedAtString = Carbon::parse($recordedAt)
            ->setMicrosecond(0)
            ->format('Y-m-d H:i:s');

        $alreadyExists = Telemetry::query()
            ->where('device_id', (int) $deviceId)
            ->where('recorded_at', $recordedAtString)
            ->exists();

        if ($alreadyExists) {
            return;
        }

        Telemetry::query()->create([
            'device_id' => (int) $deviceId,
            'recorded_at' => $recordedAtString,
            'temperature' => $payload['temperature'] ?? null,
            'voltage' => $payload['voltage'] ?? null,
            'current' => $payload['current'] ?? null,
            'power' => $payload['power'] ?? $payload['power_kw'] ?? null,
            'energy_generated' => $payload['energy_generated'] ?? null,
            'status' => $payload['status'] ?? 'OK',
        ]);
    }

    public function getEventsCursor(int $perPage = 50)
    {
        $perPage = min(max($perPage, 1), 1000);

        return TelemetryEvent::query()
            ->orderByDesc('event_timestamp')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);
    }

    public function queryEvents(array $filters = [], int $perPage = 50)
    {
        $perPage = min(max($perPage, 1), 100);

        return TelemetryEvent::query()
            ->when(
                $filters['tenant_id'] ?? null,
                fn ($query, $tenantId) => $query->where('tenant_id', $tenantId)
            )
            ->when(
                $filters['source_id'] ?? null,
                fn ($query, $sourceId) => $query->where('source_id', $sourceId)
            )
            ->when(
                $filters['event_type'] ?? null,
                fn ($query, $eventType) => $query->where('event_type', $eventType)
            )
            ->when(
                $filters['from'] ?? null,
                fn ($query, $from) => $query->where(
                    'event_timestamp',
                    '>=',
                    $from
                )
            )
            ->when(
                $filters['to'] ?? null,
                fn ($query, $to) => $query->where(
                    'event_timestamp',
                    '<=',
                    $to
                )
            )
            ->orderByDesc('event_timestamp')
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}