<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\RoleAbilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function login(
        LoginRequest $request,
        RoleAbilityService $roleAbilityService
    ): JsonResponse {
        $user = User::where('email', $request->validated('email'))->first();

        if (
            ! $user ||
            ! Hash::check($request->validated('password'), $user->password)
        ) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $abilities = $roleAbilityService->abilitiesFor($user->role);

        $token = $user->createToken(
            'solar-energy-frontend',
            $abilities
        )->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                ],
                'token' => $token,
                'abilities' => $abilities,
            ],
        ], 200);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'message' => 'Authenticated user.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                ],
                'abilities' => $user->currentAccessToken()?->abilities ?? [],
            ],
        ], 200);
    }
}
