<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Collection;

class AssetService
{
    public function list(array $filters = []): Collection
    {
        $query = Asset::query();

        if (!empty($filters['plant_id'])) {
            $query->where('plant_id', $filters['plant_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query
            ->orderByDesc('id')
            ->get();
    }

    public function create(array $data): Asset
    {
        return Asset::create($data);
    }

    public function update(Asset $asset, array $data): Asset
    {
        $asset->update($data);

        return $asset->refresh();
    }

    public function delete(Asset $asset): void
    {
        $asset->delete();
    }
}