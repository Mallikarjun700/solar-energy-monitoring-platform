<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Services\AssetService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AssetController extends Controller
{
    public function __construct(
        private readonly AssetService $assetService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $assets = $this->assetService->list([
            'plant_id' => $request->query('plant_id'),
            'status' => $request->query('status'),
        ]);

        return AssetResource::collection($assets);
    }

    public function store(StoreAssetRequest $request): AssetResource
    {
        $asset = $this->assetService->create(
            $request->validated()
        );

        return new AssetResource($asset);
    }

    public function show(Asset $asset): AssetResource
    {
        return new AssetResource($asset);
    }

    public function update(
        UpdateAssetRequest $request,
        Asset $asset
    ): AssetResource {
        $asset = $this->assetService->update(
            $asset,
            $request->validated()
        );

        return new AssetResource($asset);
    }

    public function destroy(Asset $asset): Response
    {
        $this->assetService->delete($asset);

        return response()->noContent();
    }
}
