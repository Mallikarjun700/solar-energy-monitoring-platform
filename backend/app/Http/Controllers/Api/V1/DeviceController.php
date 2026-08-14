<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeviceRequest;
use App\Http\Requests\UpdateDeviceRequest;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use App\Services\DeviceService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class DeviceController extends Controller
{
    public function __construct(
        private readonly DeviceService $deviceService
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $devices = $this->deviceService->list([
            'asset_id' => $request->query('asset_id'),
            'status' => $request->query('status'),
        ]);

        return DeviceResource::collection($devices);
    }

    public function store(StoreDeviceRequest $request): DeviceResource
    {
        $device = $this->deviceService->create(
            $request->validated()
        );

        return new DeviceResource($device);
    }

    public function show(Device $device): DeviceResource
    {
        return new DeviceResource($device);
    }

    public function update(UpdateDeviceRequest $request, Device $device): DeviceResource {
        $device = $this->deviceService->update(
            $device,
            $request->validated()
        );

        return new DeviceResource($device);
    }

    public function destroy(Device $device): Response
    {
        $this->deviceService->delete($device);

        return response()->noContent();
    }
}