<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TelemetryEventRequest;
use App\Services\TelemetryService;
use Illuminate\Http\JsonResponse;
use App\Jobs\ProcessTelemetryBatchJob;

class TelemetryController extends Controller
{
    public function __construct(
        private readonly TelemetryService $telemetryService
    ) {
    }

    public function ingest(TelemetryEventRequest $request): JsonResponse
    {
        $events = $request->validated('events');

        $chunks = array_chunk($events, 250);

        $correlationId = app()->bound('correlation_id') ? app('correlation_id') : null;
        foreach ($chunks as $chunk) {
            ProcessTelemetryBatchJob::dispatch($chunk, $correlationId);
        }

        return response()->json([
            'accepted' => count($events),
            'jobs_dispatched' => count($chunks),
        ], 202);
    }

    public function index(): JsonResponse
    {
        $events = $this->telemetryService->getAllEvents();

        return response()->json([
            'data' => $events,
        ], 200);
    }
}