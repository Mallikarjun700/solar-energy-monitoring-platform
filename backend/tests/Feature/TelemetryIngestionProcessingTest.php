<?php

namespace Tests\Feature;

use App\Enums\TokenAbility;
use App\Jobs\ProcessTelemetryBatchJob;
use App\Models\Asset;
use App\Models\Device;
use App\Models\Plant;
use App\Models\TelemetryEvent;
use App\Models\User;
use App\Services\TelemetryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class TelemetryIngestionProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_telemetry_flows_from_api_to_queue_to_postgresql_storage(): void
    {
        Queue::fake();

        $tenantId = (string) Str::uuid();
        $sourceId = (string) Str::uuid();
        $eventId = (string) Str::uuid();

        $user = User::factory()
            ->forTenant($tenantId)
            ->create();

        $plant = Plant::factory()->create([
            'tenant_id' => $tenantId,
        ]);

        $asset = Asset::factory()->create([
            'tenant_id' => $tenantId,
            'plant_id' => $plant->id,
        ]);

        $device = Device::factory()->create([
            'tenant_id' => $tenantId,
            'asset_id' => $asset->id,
        ]);

        $token = $user->createToken(
            'telemetry-ingestion-test',
            [TokenAbility::TELEMETRY_WRITE->value]
        )->plainTextToken;

        $payload = [
            'events' => [
                [
                    'event_id' => $eventId,
                    'tenant_id' => $tenantId,
                    'source_id' => $sourceId,
                    'event_type' => 'telemetry.power',
                    'timestamp' => '2026-09-27T10:00:00Z',
                    'schema_version' => 1,
                    'attributes' => [
                        'device_id' => $device->id,
                        'location' => 'Block A',
                    ],
                    'payload' => [
                        'power_kw' => 52.5,
                        'temperature' => 29.5,
                        'voltage' => 230,
                        'current' => 12.8,
                    ],
                ],
            ],
        ];

        // 1. API ingestion.
        $response = $this
            ->withToken($token)
            ->withHeader('Idempotency-Key', 'telemetry-ingestion-001')
            ->postJson('/api/v1/telemetry/events', $payload);

        $response
            ->assertStatus(202)
            ->assertJson([
                'accepted' => 1,
                'jobs_dispatched' => 1,
            ]);

        // 2. Verify the API dispatched the processing job.
        Queue::assertPushed(
            ProcessTelemetryBatchJob::class,
            function (ProcessTelemetryBatchJob $job) use ($eventId): bool {
                return count($job->events) === 1
                    && $job->events[0]['event_id'] === $eventId;
            }
        );

        $job = null;

        Queue::assertPushed(
            ProcessTelemetryBatchJob::class,
            function (ProcessTelemetryBatchJob $queuedJob) use (&$job): bool {
                $job = $queuedJob;

                return true;
            }
        );

        $this->assertNotNull($job);

        // 3. Execute the queued processing job.
        $job->handle(app(TelemetryService::class));

        // 4. Verify the processed event was persisted to telemetry storage.
        $storedEvent = TelemetryEvent::query()
            ->where('event_id', $eventId)
            ->first();

        $this->assertNotNull($storedEvent);

        $this->assertSame($eventId, $storedEvent->event_id);
        $this->assertSame($tenantId, $storedEvent->tenant_id);
        $this->assertSame($sourceId, $storedEvent->source_id);
        $this->assertSame('telemetry.power', $storedEvent->event_type);
        $this->assertSame(1, $storedEvent->schema_version);

        $this->assertSame(
            $device->id,
            $storedEvent->attributes['device_id']
        );

        $this->assertSame(
            52.5,
            $storedEvent->payload['power_kw']
        );
    }

    public function test_duplicate_event_id_is_not_stored_twice(): void
    {
        $tenantId = (string) Str::uuid();
        $sourceId = (string) Str::uuid();
        $eventId = (string) Str::uuid();

        $events = [
            [
                'event_id' => $eventId,
                'tenant_id' => $tenantId,
                'source_id' => $sourceId,
                'event_type' => 'telemetry.power',
                'timestamp' => '2026-09-27T10:00:00Z',
                'schema_version' => 1,
                'attributes' => [
                    'device_id' => 101,
                ],
                'payload' => [
                    'power_kw' => 52.5,
                ],
            ],
        ];

        $service = app(TelemetryService::class);

        $first = $service->ingest($events);
        $second = $service->ingest($events);

        $this->assertSame(1, $first['accepted']);
        $this->assertSame(0, $first['duplicates']);

        $this->assertSame(0, $second['accepted']);
        $this->assertSame(1, $second['duplicates']);

        $this->assertSame(
            1,
            TelemetryEvent::query()
                ->where('event_id', $eventId)
                ->count()
        );
    }
}
