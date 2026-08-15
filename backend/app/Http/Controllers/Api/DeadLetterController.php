<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeadLetterEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeadLetterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DeadLetterEvent::query()
            ->latest('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->value());
        }

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->string('event_id')->value());
        }

        $events = $query->paginate(
            $request->integer('per_page', 20)
        );

        return response()->json($events);
    }
}