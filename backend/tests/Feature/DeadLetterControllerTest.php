<?php

namespace Tests\Feature;

use App\Enums\DeadLetterStatus;
use App\Models\DeadLetterEvent;
use App\Models\Telemetry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeadLetterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dlq_events_can_be_listed(): void
    {
        DeadLetterEvent::create([
            'event_id' => 'evt-123',
            'device_id' => 101,
            'original_payload' => [
                'power_kw' => 52.4,
            ],
            'error_type' => 'PROCESSING_ERROR',
            'failure_reason' => 'Database unavailable',
            'attempt_count' => 3,
            'first_failed_at' => now(),
            'last_failed_at' => now(),
            'status' => DeadLetterStatus::PENDING,
        ]);

        $response = $this->getJson('/v1/dlq');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.event_id', 'evt-123');
    }

    public function test_dlq_event_can_be_replayed(): void
    {
        $deadLetterEvent = DeadLetterEvent::create([
            'event_id' => 'evt-replay-001',
            'device_id' => 101,
            'original_payload' => [
                'event_id' => 'evt-replay-001',
                'device_id' => 101,
                'power_kw' => 52.4,
            ],
            'error_type' => 'PROCESSING_ERROR',
            'failure_reason' => 'Temporary database failure',
            'attempt_count' => 3,
            'first_failed_at' => now(),
            'last_failed_at' => now(),
            'status' => DeadLetterStatus::PENDING,
        ]);

        $response = $this->postJson("/v1/dlq/{$deadLetterEvent->id}/replay", [
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

    public function test_resolved_dlq_event_cannot_be_replayed_again(): void
    {
        $deadLetterEvent = DeadLetterEvent::create([
            'event_id' => 'evt-resolved-001',
            'device_id' => 101,
            'original_payload' => [
                'event_id' => 'evt-resolved-001',
            ],
            'error_type' => 'PROCESSING_ERROR',
            'failure_reason' => 'Temporary failure',
            'attempt_count' => 3,
            'status' => DeadLetterStatus::RESOLVED,
        ]);

        $response = $this->postJson("/v1/dlq/{$deadLetterEvent->id}/replay");

        $response->assertStatus(409);
    }

    public function test_dlq_replay_does_not_create_duplicate_telemetry(): void
    {
        Telemetry::create([
            'device_id' => 109,
            'recorded_at' => now(),
            'temperature' => 35.5,
            'voltage' => 230,
            'current' => 12.8,
            'power' => 2.9,
            'energy_generated' => 0.0,
            'status' => DeadLetterStatus::RESOLVED,
        ]);

        $this->assertDatabaseCount('telemetry', 1);

        $deadLetterEvent = DeadLetterEvent::create([
            'event_id' => 'evt-replay-001',
            'device_id' => 101,
            'original_payload' => [
                'event_id' => 'evt-replay-001',
                'device_id' => 101,
                'power_kw' => 52.4,
            ],
            'error_type' => 'PROCESSING_ERROR',
            'failure_reason' => 'Temporary database failure',
            'attempt_count' => 3,
            'first_failed_at' => now(),
            'last_failed_at' => now(),
            'status' => DeadLetterStatus::PENDING,
        ]);

        $this->postJson("/v1/dlq/{$deadLetterEvent->id}/replay");

        $this->assertDatabaseCount('telemetry', 1);
    }
}