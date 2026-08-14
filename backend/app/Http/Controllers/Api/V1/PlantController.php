<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Plant;
use Illuminate\Http\Request;

class PlantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $plants = Plant::latest()->get();

        return response()->json([
            'data' => $plants,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity_kw' => ['nullable', 'numeric'],
            'status' => ['nullable', 'in:active,inactive,maintenance'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $plant = Plant::create($validated);

        return response()->json([
            'message' => 'Plant created successfully.',
            'data' => $plant,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Plant $plant)
    {
        return response()->json([
            'data' => $plant,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Plant $plant)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'capacity_kw' => ['sometimes', 'nullable', 'numeric'],
            'status' => ['sometimes', 'nullable', 'in:active,inactive,maintenance'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
        ]);

        $plant->update($validated);

        return response()->json([
            'message' => 'Plant updated successfully.',
            'data' => $plant,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Plant $plant)
    {
        $plant->delete();

        return response()->json([
            'message' => 'Plant deleted successfully.',
        ], 200);
    }
}
