<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Services\AssetService;
use App\Services\Tenant\TenantContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AssetController extends Controller
{
    public function __construct(
        private readonly AssetService $assetService,
        private readonly TenantContextService $tenantContextService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantContextService
            ->resolveForUser($request);

        return response()->json([
            'data' => AssetResource::collection(
                $this->assetService->list(
                    $tenantId,
                    $request->only(['plant_id', 'status'])
                )
            ),
        ]);
    }

    public function store(StoreAssetRequest $request): JsonResponse
    {
        $tenantId = $this->tenantContextService
            ->resolveForUser($request);

        $asset = $this->assetService->create(
            $request->validated(),
            $tenantId
        );

        return response()->json([
            'message' => 'Asset created successfully.',
            'data' => new AssetResource($asset),
        ], 201);
    }

    public function show(Request $request, Asset $asset): AssetResource
    {
        $tenantId = $this->tenantContextService
            ->resolveForUser($request);

        return new AssetResource(
            $this->assetService->find($asset->id, $tenantId)
        );
    }

    public function update(
        UpdateAssetRequest $request,
        Asset $asset
    ): JsonResponse {
        $tenantId = $this->tenantContextService
            ->resolveForUser($request);

        $asset = $this->assetService->update(
            $asset,
            $request->validated(),
            $tenantId
        );

        return response()->json([
            'message' => 'Asset updated successfully.',
            'data' => new AssetResource($asset),
        ]);
    }

    public function destroy(
        Request $request,
        Asset $asset
    ): Response {
        $tenantId = $this->tenantContextService
            ->resolveForUser($request);

        $this->assetService->delete($asset, $tenantId);

        return response()->noContent();
    }
}
