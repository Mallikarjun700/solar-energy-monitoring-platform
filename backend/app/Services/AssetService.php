<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Plant;
use Illuminate\Database\Eloquent\Collection;

class AssetService
{
    public function list(
        string $tenantId,
        array $filters = []
    ): Collection {
        $query = Asset::query()
            ->where('tenant_id', $tenantId);

        if (! empty($filters['plant_id'])) {
            $query->where('plant_id', $filters['plant_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query
            ->orderByDesc('id')
            ->get();
    }

    public function find(int $assetId, string $tenantId): Asset
    {
        return Asset::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($assetId);
    }

    public function create(array $data, string $tenantId): Asset
    {
        $plant = Plant::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($data['plant_id']);

        $data['tenant_id'] = $tenantId;

        return Asset::create($data);
    }

    public function update(
        Asset $asset,
        array $data,
        string $tenantId
    ): Asset {
        if ($asset->tenant_id !== $tenantId) {
            abort(404);
        }

        if (isset($data['plant_id'])) {
            Plant::query()
                ->where('tenant_id', $tenantId)
                ->findOrFail($data['plant_id']);
        }

        $data['tenant_id'] = $tenantId;

        $asset->update($data);

        return $asset->refresh();
    }

    public function delete(Asset $asset, string $tenantId): void
    {
        if ($asset->tenant_id !== $tenantId) {
            abort(404);
        }

        $asset->delete();
    }
}
