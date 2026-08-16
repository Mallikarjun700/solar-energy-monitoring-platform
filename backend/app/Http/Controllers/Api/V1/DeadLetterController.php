<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeadLetterEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Enums\DeadLetterStatus;
use App\Services\TelemetryService;

class DeadLetterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DeadLetterEvent::query()
            ->latest('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->value());
        }

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->string('event_id')->value());
        }

        $events = $query->paginate(
            $request->integer('per_page', 20)
        );

        return response()->json($events);
    }

    public function replay(DeadLetterEvent $deadLetterEvent, TelemetryService $telemetryService): JsonResponse {
        if ($deadLetterEvent->status === DeadLetterStatus::RESOLVED) {
            return response()->json([
                'message' => 'DLQ event has already been resolved.',
            ], 409);
        }

        $deadLetterEvent->update([
            'status' => DeadLetterStatus::REPLAYED,
        ]);

        try {
            $telemetryService->process(
                $deadLetterEvent->original_payload
            );

            $deadLetterEvent->update([
                'status' => DeadLetterStatus::RESOLVED,
                'last_failed_at' => now(),
            ]);

            return response()->json([
                'message' => 'DLQ event replayed successfully.',
                'data' => $deadLetterEvent->fresh(),
            ]);
        } catch (\Throwable $exception) {
            $deadLetterEvent->update([
                'status' => DeadLetterStatus::FAILED,
                'failure_reason' => $exception->getMessage(),
                'last_failed_at' => now(),
            ]);

            return response()->json([
                'message' => 'DLQ event replay failed.',
                'error' => $exception->getMessage(),
            ], 422);
        }
    }
}