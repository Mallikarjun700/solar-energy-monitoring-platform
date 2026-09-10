<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'tenant_id' => ['required', 'uuid'],
        ])->validate();

        return response()->json([
            'status' => 'success',
            'message' => 'Dashboard data retrieved successfully.',
            'data' => $this->dashboardService->getDashboard(
                $validated['tenant_id']
            ),
        ]);
    }
}
