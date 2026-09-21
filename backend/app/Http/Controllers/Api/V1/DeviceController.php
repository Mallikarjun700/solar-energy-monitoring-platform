<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeviceRequest;
use App\Http\Requests\UpdateDeviceRequest;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use App\Services\DeviceService;
use App\Services\Tenant\TenantContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeviceController extends Controller
{
    public function __construct(
        private readonly DeviceService $deviceService,
        private readonly TenantContextService $tenantContextService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantContextService
            ->resolveForUser($request);

        return response()->json([
            'data' => DeviceResource::collection(
                $this->deviceService->list(
                    $tenantId,
                    $request->only(['asset_id', 'status'])
                )
            ),
        ]);
    }

    public function store(StoreDeviceRequest $request): JsonResponse
    {
        $tenantId = $this->tenantContextService
            ->resolveForUser($request);

        $device = $this->deviceService->create(
            $request->validated(),
            $tenantId
        );

        return response()->json([
            'message' => 'Device created successfully.',
            'data' => new DeviceResource($device),
        ], 201);
    }

    public function show(
        Request $request,
        Device $device
    ): DeviceResource {
        $tenantId = $this->tenantContextService
            ->resolveForUser($request);

        return new DeviceResource(
            $this->deviceService->find($device->id, $tenantId)
        );
    }

    public function update(
        UpdateDeviceRequest $request,
        Device $device
    ): JsonResponse {
        $tenantId = $this->tenantContextService
            ->resolveForUser($request);

        $device = $this->deviceService->update(
            $device,
            $request->validated(),
            $tenantId
        );

        return response()->json([
            'message' => 'Device updated successfully.',
            'data' => new DeviceResource($device),
        ]);
    }

    public function destroy(
        Request $request,
        Device $device
    ): Response {
        $tenantId = $this->tenantContextService
            ->resolveForUser($request);

        $this->deviceService->delete($device, $tenantId);

        return response()->json([
            'message' => 'Device deleted successfully.',
        ]);
    }
}
