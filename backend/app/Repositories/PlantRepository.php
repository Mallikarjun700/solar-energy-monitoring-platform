<?php

namespace App\Repositories;

use App\Models\Plant;

class PlantRepository
{
    public function create(array $data): Plant
    {
        return Plant::create($data);
    }

    public function all()
    {
        return Plant::latest()->get();
    }

    public function find(int $id): ?Plant
    {
        return Plant::find($id);
    }
}
