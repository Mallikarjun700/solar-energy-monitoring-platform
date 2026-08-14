<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Database\Eloquent\Collection;

class DeviceService
{
    public function list(array $filters = []): Collection
    {
        $query = Device::query();

        if (!empty($filters['asset_id'])) {
            $query->where('asset_id', $filters['asset_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query
            ->orderByDesc('id')
            ->get();
    }

    public function create(array $data): Device
    {
        return Device::create($data);
    }

    public function update(Device $device, array $data): Device
    {
        $device->update($data);

        return $device->refresh();
    }

    public function delete(Device $device): void
    {
        $device->delete();
    }
}