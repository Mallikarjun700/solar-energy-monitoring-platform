<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TelemetryEventRequest;
use App\Services\TelemetryService;
use Illuminate\Http\JsonResponse;

class TelemetryController extends Controller
{
    public function __construct(
        private readonly TelemetryService $telemetryService
    ) {
    }

    public function ingest(TelemetryEventRequest $request): JsonResponse
    {
        $events = $request->validated('events');

        $result = $this->telemetryService->ingest($events);

        return response()->json($result, 202);
    }

    public function index(): JsonResponse
    {
        $events = $this->telemetryService->getAllEvents();

        return response()->json([
            'data' => $events,
        ], 200);
    }
}