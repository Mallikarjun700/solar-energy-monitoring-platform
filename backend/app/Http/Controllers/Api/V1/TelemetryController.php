<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TelemetryEventRequest;
use App\Http\Requests\TelemetryQueryRequest;
use App\Services\TelemetryService;
use Illuminate\Http\JsonResponse;
use App\Jobs\ProcessTelemetryBatchJob;
use Illuminate\Http\Request;

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

    public function index(TelemetryQueryRequest $request): JsonResponse
    {
        $filters = $request->only([
            'tenant_id',
            'source_id',
            'event_type',
            'from',
            'to',
        ]);

        $perPage = (int) $request->query('per_page', 50);

        $events = $this->telemetryService->queryEvents(
            $filters,
            $perPage
        );

        return response()->json($events, 200);
    }

    public function health(): JsonResponse
    {
        $health = app(\App\Services\TelemetryHealthService::class)
            ->check();

        $statusCode = $health['status'] === 'healthy' ? 200 : 503;

        return response()->json(
            $health,
            $statusCode
        );
    }

    public function latest(Request $request,string $deviceId): JsonResponse {
        $tenantId = $request->query('tenant_id');

        if (! $tenantId) {
            return response()->json([
                'message' => 'tenant_id is required.',
            ], 422);
        }

        $latest = $this->telemetryService->getLatest(
            $tenantId,
            $deviceId
        );

        if ($latest === null) {
            return response()->json([
                'message' => 'Latest telemetry not found.',
            ], 404);
        }

        return response()->json($latest, 200);
    }
}
