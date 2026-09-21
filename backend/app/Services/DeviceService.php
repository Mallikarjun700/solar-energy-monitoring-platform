<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Device;
use Illuminate\Database\Eloquent\Collection;

class DeviceService
{
    public function list(
        string $tenantId,
        array $filters = []
    ): Collection {
        $query = Device::query()
            ->where('tenant_id', $tenantId);

        if (! empty($filters['asset_id'])) {
            $query->where('asset_id', $filters['asset_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query
            ->orderByDesc('id')
            ->get();
    }

    public function find(int $deviceId, string $tenantId): Device
    {
        return Device::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($deviceId);
    }

    public function create(array $data, string $tenantId): Device
    {
        Asset::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($data['asset_id']);

        $data['tenant_id'] = $tenantId;

        return Device::create($data);
    }

    public function update(
        Device $device,
        array $data,
        string $tenantId
    ): Device {
        if ($device->tenant_id !== $tenantId) {
            abort(404);
        }

        if (isset($data['asset_id'])) {
            Asset::query()
                ->where('tenant_id', $tenantId)
                ->findOrFail($data['asset_id']);
        }

        $data['tenant_id'] = $tenantId;

        $device->update($data);

        return $device->refresh();
    }

    public function delete(Device $device, string $tenantId): void
    {
        if ($device->tenant_id !== $tenantId) {
            abort(404);
        }

        $device->delete();
    }
}
