<?php

namespace Tests\Unit\Services;

use App\Enums\DeadLetterStatus;
use App\Models\DeadLetterEvent;
use App\Services\DeadLetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeadLetterServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_event_is_captured_in_dlq(): void
    {
        $service = app(DeadLetterService::class);

        $event = $service->captureFailedEvent(
            'evt-123',
            101,
            [
                'event_id' => 'evt-123',
                'device_id' => 101,
                'power_kw' => 52.4,
            ],
            'Database connection failed',
            3
        );

        $this->assertInstanceOf(
            DeadLetterEvent::class,
            $event
        );

        $this->assertSame(
            'evt-123',
            $event->event_id
        );

        $this->assertSame(
            101,
            $event->device_id
        );

        $this->assertSame(
            3,
            $event->attempt_count
        );

        $this->assertSame(
            DeadLetterStatus::PENDING,
            $event->status
        );

        $this->assertDatabaseHas(
            'dead_letter_events',
            [
                'event_id' => 'evt-123',
                'device_id' => 101,
                'attempt_count' => 3,
                'status' => DeadLetterStatus::PENDING->value,
            ]
        );
    }
}