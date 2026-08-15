<?php

namespace App\Services;

use App\Enums\DeadLetterStatus;
use App\Models\DeadLetterEvent;
use Throwable;

class DeadLetterService
{
    /**
     * Capture a failed telemetry event in the DLQ.
     */
    public function captureFailedEvent(string $eventId, ?int $deviceId, array $payload, Throwable|string $error, int $attemptCount = 0 ): DeadLetterEvent {
        $errorType = $error instanceof Throwable
            ? get_class($error)
            : 'PROCESSING_ERROR';

        $failureReason = $error instanceof Throwable
            ? $error->getMessage()
            : $error;

        return DeadLetterEvent::create([
            'event_id' => $eventId,
            'device_id' => $deviceId,
            'original_payload' => $payload,
            'error_type' => $errorType,
            'failure_reason' => $failureReason,
            'attempt_count' => $attemptCount,
            'first_failed_at' => now(),
            'last_failed_at' => now(),
            'status' => DeadLetterStatus::PENDING,
        ]);
    }
}