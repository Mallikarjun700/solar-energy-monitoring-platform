<?php

namespace Tests\Feature;

use App\Enums\DeadLetterStatus;
use App\Models\DeadLetterEvent;
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
}