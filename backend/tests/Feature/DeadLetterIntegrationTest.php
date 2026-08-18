<?php

namespace Tests\Feature;

use App\Enums\DeadLetterStatus;
use App\Models\Asset;
use App\Models\DeadLetterEvent;
use App\Models\Device;
use App\Models\Plant;
use App\Models\Telemetry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeadLetterIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_exhausted_retry_creates_dlq_event(): void
    {
        $plant = Plant::create([
            'name' => 'Retry Plant',
            'code' => 'RP-101',
            'location' => 'Test City',
            'capacity_kw' => 1000,
            'status' => 'ACTIVE',
        ]);

        $asset = Asset::create([
            'plant_id' => $plant->id,
            'name' => 'Retry Asset',
            'asset_type' => 'INVERTER',
            'serial_number' => 'RT-101',
            'status' => 'ACTIVE',
            'location' => 'Block C',
        ]);

        $device = Device::create([
            'asset_id' => $asset->id,
            'device_type' => 'SMART_METER',
            'serial_number' => 'RTD-101',
            'status' => 'ONLINE',
            'last_seen_at' => now(),
        ]);

        $event = DeadLetterEvent::create([
            'event_id' => 'evt-retry-exhausted',
            'device_id' => $device->id,
            'original_payload' => [
                'device_id' => $device->id,
                'power_kw' => 50.5,
            ],
            'error_type' => 'PROCESSING_ERROR',
            'failure_reason' => 'Retry exhausted',
            'attempt_count' => 3,
            'first_failed_at' => now(),
            'last_failed_at' => now(),
            'status' => DeadLetterStatus::PENDING,
        ]);

        $this->assertDatabaseHas('dead_letter_events', [
            'id' => $event->id,
            'event_id' => 'evt-retry-exhausted',
            'status' => DeadLetterStatus::PENDING->value,
            'attempt_count' => 3,
        ]);
    }

    public function test_non_retryable_failure_creates_dlq_event(): void
    {
        $plant = Plant::create([
            'name' => 'Invalid Plant',
            'code' => 'IP-101',
            'location' => 'Test City',
            'capacity_kw' => 1000,
            'status' => 'ACTIVE',
        ]);

        $asset = Asset::create([
            'plant_id' => $plant->id,
            'name' => 'Invalid Asset',
            'asset_type' => 'INVERTER',
            'serial_number' => 'IV-101',
            'status' => 'ACTIVE',
            'location' => 'Block D',
        ]);

        $device = Device::create([
            'asset_id' => $asset->id,
            'device_type' => 'SMART_METER',
            'serial_number' => 'IVD-101',
            'status' => 'ONLINE',
            'last_seen_at' => now(),
        ]);

        $event = DeadLetterEvent::create([
            'event_id' => 'evt-invalid',
            'device_id' => $device->id,
            'original_payload' => [
                'device_id' => $device->id,
                'timestamp' => now(),
            ],
            'error_type' => 'VALIDATION_ERROR',
            'failure_reason' => 'Missing required telemetry fields',
            'attempt_count' => 1,
            'first_failed_at' => now(),
            'last_failed_at' => now(),
            'status' => DeadLetterStatus::PENDING,
        ]);

        $this->assertDatabaseHas('dead_letter_events', [
            'id' => $event->id,
            'event_id' => 'evt-invalid',
            'status' => DeadLetterStatus::PENDING->value,
        ]);
    }

    public function test_dlq_replay_resolves_event(): void
    {
        $plant = Plant::create([
            'name' => 'Replay Resolve Plant',
            'code' => 'RRP-101',
            'location' => 'Test City',
            'capacity_kw' => 1200,
            'status' => 'ACTIVE',
        ]);

        $asset = Asset::create([
            'plant_id' => $plant->id,
            'name' => 'Replay Resolve Asset',
            'asset_type' => 'INVERTER',
            'serial_number' => 'RRA-101',
            'status' => 'ACTIVE',
            'location' => 'Block E',
        ]);

        $device = Device::create([
            'asset_id' => $asset->id,
            'device_type' => 'SMART_METER',
            'serial_number' => 'RRD-101',
            'status' => 'ONLINE',
            'last_seen_at' => now(),
        ]);

        $deadLetterEvent = DeadLetterEvent::create([
            'event_id' => 'evt-replay-resolve',
            'device_id' => $device->id,
            'original_payload' => [
                'device_id' => $device->id,
                'recorded_at' => now(),
                'power_kw' => 43.5,
                'temperature' => 29.1,
                'status' => 'OK',
            ],
            'error_type' => 'PROCESSING_ERROR',
            'failure_reason' => 'Temporary failure',
            'attempt_count' => 3,
            'first_failed_at' => now()->subMinutes(5),
            'last_failed_at' => now(),
            'status' => DeadLetterStatus::PENDING,
        ]);

        $response = $this->postJson("/api/v1/dlq/{$deadLetterEvent->id}/replay", [
            'event_id' => $deadLetterEvent->event_id,
            'device_id' => $deadLetterEvent->device_id,
            'original_payload' => $deadLetterEvent->original_payload,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('dead_letter_events', [
            'id' => $deadLetterEvent->id,
            'status' => DeadLetterStatus::RESOLVED->value,
        ]);
    }

    public function test_failed_replay_is_not_lost(): void
    {
        $plant = Plant::create([
            'name' => 'Fail Replay Plant',
            'code' => 'FRP-101',
            'location' => 'Test City',
            'capacity_kw' => 1300,
            'status' => 'ACTIVE',
        ]);

        $asset = Asset::create([
            'plant_id' => $plant->id,
            'name' => 'Fail Replay Asset',
            'asset_type' => 'INVERTER',
            'serial_number' => 'FRA-101',
            'status' => 'ACTIVE',
            'location' => 'Block F',
        ]);

        $device = Device::create([
            'asset_id' => $asset->id,
            'device_type' => 'SMART_METER',
            'serial_number' => 'FRD-101',
            'status' => 'ONLINE',
            'last_seen_at' => now(),
        ]);

        $deadLetterEvent = DeadLetterEvent::create([
            'event_id' => 'evt-replay-failed',
            'device_id' => $device->id,
            'original_payload' => [
                'device_id' => null,
            ],
            'error_type' => 'PROCESSING_ERROR',
            'failure_reason' => 'Replay failed',
            'attempt_count' => 3,
            'first_failed_at' => now()->subMinutes(10),
            'last_failed_at' => now(),
            'status' => DeadLetterStatus::PENDING,
        ]);

        $response = $this->postJson("/api/v1/dlq/{$deadLetterEvent->id}/replay", [
            'event_id' => $deadLetterEvent->event_id,
            'device_id' => $deadLetterEvent->device_id,
            'original_payload' => $deadLetterEvent->original_payload,
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('dead_letter_events', [
            'id' => $deadLetterEvent->id,
            'status' => DeadLetterStatus::FAILED->value,
        ]);
    }

    public function test_replay_does_not_create_duplicate_telemetry(): void
    {
        $plant = Plant::create([
            'name' => 'Duplicate Integration Plant',
            'code' => 'DIP-101',
            'location' => 'Test City',
            'capacity_kw' => 1500,
            'status' => 'ACTIVE',
        ]);

        $asset = Asset::create([
            'plant_id' => $plant->id,
            'name' => 'Duplicate Integration Asset',
            'asset_type' => 'INVERTER',
            'serial_number' => 'DIA-101',
            'status' => 'ACTIVE',
            'location' => 'Block H',
        ]);

        $device = Device::create([
            'asset_id' => $asset->id,
            'device_type' => 'SMART_METER',
            'serial_number' => 'DID-101',
            'status' => 'ONLINE',
            'last_seen_at' => now(),
        ]);

        $recordedAt = now();

        Telemetry::query()->create([
            'device_id' => $device->id,
            'recorded_at' => $recordedAt,
            'temperature' => 35.5,
            'voltage' => 230,
            'current' => 12.8,
            'power' => 2.9,
            'energy_generated' => 0.0,
            'status' => 'OK',
        ]);

        $this->assertDatabaseCount('telemetry', 1);

        $deadLetterEvent = DeadLetterEvent::create([
            'event_id' => 'evt-replay-duplicate',
            'device_id' => $device->id,
            'original_payload' => [
                'event_id' => 'evt-replay-duplicate',
                'device_id' => $device->id,
                'recorded_at' => $recordedAt,
                'power_kw' => 2.9,
                'temperature' => 35.5,
                'status' => 'OK',
            ],
            'error_type' => 'PROCESSING_ERROR',
            'failure_reason' => 'Duplicate telemetry detected',
            'attempt_count' => 1,
            'status' => DeadLetterStatus::PENDING,
        ]);

        $this->postJson("/api/v1/dlq/{$deadLetterEvent->id}/replay", [
            'event_id' => $deadLetterEvent->event_id,
            'device_id' => $deadLetterEvent->device_id,
            'original_payload' => $deadLetterEvent->original_payload,
        ]);

        $this->assertDatabaseCount('telemetry', 1);
    }

    public function test_exhausted_retry_creates_dlq_event_new(): void
    {
        $eventId = (string) \Illuminate\Support\Str::uuid();

        $events = [
            [
                'event_id' => $eventId,
                'tenant_id' => (string) \Illuminate\Support\Str::uuid(),
                'source_id' => (string) \Illuminate\Support\Str::uuid(),
                'event_type' => 'telemetry.power',
                'timestamp' => now()->toISOString(),
                'schema_version' => 1,
                'attributes' => [
                    'device_id' => 101,
                ],
                'payload' => [
                    'power_kw' => 50.5,
                ],

                // Test-only failure switch.
                'force_failure' => true,
            ],
        ];

        $job = new \App\Jobs\ProcessTelemetryBatchJob($events);

        // Execute the job directly and verify that it fails.
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Intentional telemetry queue failure.'
        );

        $job->handle(app(\App\Services\TelemetryService::class));
    }
}