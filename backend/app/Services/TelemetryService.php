<?php

namespace App\Services;

use App\Models\Telemetry;
use App\Models\TelemetryEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

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

        $inserted = TelemetryEvent::query()->insertOrIgnore($rows);

        $accepted = $inserted;
        $duplicates = count($rows) - $accepted;

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

    public function getAllEvents(): array
    {
        return TelemetryEvent::query()
            ->orderBy('event_timestamp', 'desc')
            ->limit(100)
            ->get()->toArray();
    }
}