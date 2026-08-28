<?php

namespace Tests\Feature;

use App\Enums\DeadLetterStatus;
use App\Enums\TokenAbility;
use App\Models\Asset;
use App\Models\DeadLetterEvent;
use App\Models\Device;
use App\Models\Plant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeadLetterReplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateForApi([
            TokenAbility::DLQ_REPLAY->value,
        ]);
    }

    public function test_replay_persists_telemetry_record(): void
    {
        $plant = Plant::create([
            'name' => 'Replay Plant',
            'code' => 'RP-001',
            'location' => 'Test City',
            'capacity_kw' => 5000,
            'status' => 'ACTIVE',
        ]);

        $asset = Asset::create([
            'plant_id' => $plant->id,
            'name' => 'Replay Asset',
            'asset_type' => 'INVERTER',
            'serial_number' => 'RA-001',
            'status' => 'ACTIVE',
            'location' => 'Block A',
        ]);

        $device = Device::create([
            'asset_id' => $asset->id,
            'device_type' => 'SMART_METER',
            'serial_number' => 'RD-001',
            'status' => 'ONLINE',
            'last_seen_at' => now(),
        ]);

        $recordedAt = now();

        $deadLetterEvent = DeadLetterEvent::create([
            'event_id' => (string) Str::uuid(),
            'device_id' => $device->id,
            'original_payload' => [
                'device_id' => $device->id,
                'recorded_at' => $recordedAt,
                'temperature' => 25.5,
                'voltage' => 400,
                'current' => 10,
                'power' => 4,
                'energy_generated' => 100,
                'status' => 'OK',
            ],
            'error_type' => 'PROCESSING_ERROR',
            'failure_reason' => 'Test failure',
            'attempt_count' => 3,
            'first_failed_at' => now(),
            'last_failed_at' => now(),
            'status' => DeadLetterStatus::PENDING,
        ]);

        $response = $this->postJson("/api/v1/dlq/{$deadLetterEvent->id}/replay");
        $response->assertOk();

        $deadLetterEvent->refresh();

        $this->assertSame(DeadLetterStatus::RESOLVED, $deadLetterEvent->status);

        $this->assertDatabaseHas('telemetry', [
            'device_id' => $device->id,
            'status' => 'OK',
        ]);
    }

    public function test_replay_with_invalid_payload_transitions_to_failed(): void
    {
        $deadLetterEvent = DeadLetterEvent::create([
            'event_id' => (string) Str::uuid(),
            'device_id' => 999,
            'original_payload' => [
                'device_id' => null,
                'recorded_at' => now(),
            ],
            'error_type' => 'VALIDATION_ERROR',
            'failure_reason' => 'Missing device_id',
            'attempt_count' => 1,
            'first_failed_at' => now(),
            'last_failed_at' => now(),
            'status' => DeadLetterStatus::PENDING,
        ]);

        $response = $this->postJson("/api/v1/dlq/{$deadLetterEvent->id}/replay");

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'DLQ event replay failed.');

        $deadLetterEvent->refresh();

        $this->assertSame(DeadLetterStatus::FAILED, $deadLetterEvent->status);
        $this->assertNotNull($deadLetterEvent->failure_reason);
    }

    public function test_replay_resolved_event_returns_409(): void
    {
        $deadLetterEvent = DeadLetterEvent::create([
            'event_id' => (string) Str::uuid(),
            'device_id' => 1,
            'original_payload' => [
                'device_id' => 1,
                'recorded_at' => now(),
                'temperature' => 20.0,
                'status' => 'OK',
            ],
            'error_type' => 'PROCESSING_ERROR',
            'failure_reason' => 'Already resolved',
            'attempt_count' => 1,
            'first_failed_at' => now(),
            'last_failed_at' => now(),
            'status' => DeadLetterStatus::RESOLVED,
        ]);

        $response = $this->postJson("/api/v1/dlq/{$deadLetterEvent->id}/replay");

        $response->assertStatus(409)->assertJson([
            'status' => 'error',
            'message' => 'DLQ event has already been resolved.',
        ]);

        $deadLetterEvent->refresh();

        $this->assertSame(DeadLetterStatus::RESOLVED, $deadLetterEvent->status);
    }
}
