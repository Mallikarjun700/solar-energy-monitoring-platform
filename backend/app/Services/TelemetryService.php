<?php

namespace App\Services;

use App\Models\TelemetryEvent;
use Illuminate\Support\Facades\DB;

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

        /*
         * PostgreSQL performs the conflict handling.
         *
         * Important:
         * insertOrIgnore() will ignore rows that violate
         * the UNIQUE constraint.
         */
        
        $inserted = TelemetryEvent::query()->insertOrIgnore($rows);

        $accepted = $inserted;

        $duplicates = count($rows) - $accepted;

        return [
            'accepted' => $accepted,
            'duplicates' => $duplicates,
            'rejected' => 0,
        ];
    }

    public function getAllEvents(): array
    {
        return TelemetryEvent::query()
            ->orderBy('event_timestamp', 'desc')
            ->limit(100)
            ->get()->toArray();
    }
}