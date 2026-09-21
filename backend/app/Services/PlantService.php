<?php

namespace App\Services;

use App\Models\Plant;
use Illuminate\Database\Eloquent\Collection;

class PlantService
{
    public function getPlants(string $tenantId): Collection
    {
        return Plant::query()
            ->where('tenant_id', $tenantId)
            ->latest()
            ->get();
    }

    public function getPlant(int $plantId, string $tenantId): Plant
    {
        return Plant::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($plantId);
    }

    public function createPlant(array $data, string $tenantId): Plant
    {
        $data['tenant_id'] = $tenantId;

        return Plant::create($data);
    }
}
