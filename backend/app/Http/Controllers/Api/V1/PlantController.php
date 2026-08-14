<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlantRequest;
use App\Services\PlantService;
use Illuminate\Http\Request;

class PlantController extends Controller
{
    public function __construct(protected PlantService $plantService) {}
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json([
            'data' => $this->plantService->getPlants(),
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePlantRequest $request)
    {
        $plant = $this->plantService->createPlant($request->validated());

        return response()->json([
            'message' => 'Plant created successfully.',
            'data' => $plant,
        ], 201);
    }
}
