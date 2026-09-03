<?php

namespace App\Services;

use App\Repositories\PlantRepository;

class PlantService
{
    public function __construct(protected PlantRepository $plantRepository) {}

    public function createPlant(array $data)
    {
        return $this->plantRepository->create($data);
    }

    public function getPlants()
    {
        return $this->plantRepository->all();
    }
}
