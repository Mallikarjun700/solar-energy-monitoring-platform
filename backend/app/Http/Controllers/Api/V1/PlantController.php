<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlantRequest;
use App\Http\Resources\PlantResource;
use App\Models\Plant;
use App\Services\PlantService;
use App\Services\Tenant\TenantContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlantController extends Controller
{
    public function __construct(
        protected PlantService $plantService,
        protected TenantContextService $tenantContextService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantContextService
            ->resolveForUser($request);

        return response()->json([
            'data' => PlantResource::collection(
                $this->plantService->getPlants($tenantId)
            ),
        ]);
    }

    public function show(Request $request, Plant $plant): PlantResource
    {
        $tenantId = $this->tenantContextService
            ->resolveForUser($request);

        $plant = $this->plantService->getPlant(
            $plant->id,
            $tenantId
        );

        return new PlantResource($plant);
    }

    public function store(
        StorePlantRequest $request
    ): JsonResponse {
        $tenantId = $this->tenantContextService
            ->resolveForUser($request, false);

        $plant = $this->plantService->createPlant(
            $request->validated(),
            $tenantId
        );

        return response()->json([
            'message' => 'Plant created successfully.',
            'data' => new PlantResource($plant),
        ], 201);
    }
}
