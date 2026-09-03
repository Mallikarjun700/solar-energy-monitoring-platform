<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AlertStatus;
use App\Http\Controllers\Controller;
use App\Models\Alert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => [
                'required',
                'uuid',
            ],

            'status' => [
                'nullable',
                'string',
                'in:open,acknowledged,resolved',
            ],

            'severity' => [
                'nullable',
                'string',
                'in:info,warning,critical,emergency',
            ],

            'alert_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'device_id' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'rule_id' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'from' => [
                'nullable',
                'date',
            ],

            'to' => [
                'nullable',
                'date',
                'after_or_equal:from',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $perPage = $validated['per_page'] ?? 50;

        $alerts = Alert::query()
            ->where('tenant_id', $validated['tenant_id'])
            ->when(
                $validated['status'] ?? null,
                fn ($query, $status) => $query->where('status', $status)
            )
            ->when(
                $validated['severity'] ?? null,
                fn ($query, $severity) => $query->where('severity', $severity)
            )
            ->when(
                $validated['alert_type'] ?? null,
                fn ($query, $type) => $query->where('alert_type', $type)
            )
            ->when(
                $validated['device_id'] ?? null,
                fn ($query, $deviceId) => $query->where('device_id', $deviceId)
            )
            ->when(
                $validated['rule_id'] ?? null,
                fn ($query, $ruleId) => $query->where('rule_id', $ruleId)
            )
            ->when(
                $validated['from'] ?? null,
                fn ($query, $from) => $query->where('triggered_at', '>=', $from)
            )
            ->when(
                $validated['to'] ?? null,
                fn ($query, $to) => $query->where('triggered_at', '<=', $to)
            )
            ->orderByDesc('triggered_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json($alerts);
    }

    public function show(
        Request $request,
        Alert $alert
    ): JsonResponse {
        abort_unless(
            $alert->tenant_id === $request->query('tenant_id'),
            404
        );

        return response()->json(
            $alert->load('rule')
        );
    }

    public function acknowledge(Request $request, Alert $alert): JsonResponse
    {
        abort_unless(
            $alert->tenant_id === $request->query('tenant_id'),
            404
        );

        if ($alert->status !== AlertStatus::OPEN) {
            return response()->json([
                'message' => 'Only open alerts can be acknowledged.',
            ], 409);
        }

        $alert->update([
            'status' => AlertStatus::ACKNOWLEDGED,
            'acknowledged_at' => now(),
        ]);

        return response()->json(
            $alert->fresh(),
            200
        );
    }

    public function resolve(Request $request, Alert $alert): JsonResponse
    {
        abort_unless(
            $alert->tenant_id === $request->query('tenant_id'),
            404
        );

        if (! in_array(
            $alert->status, [
                AlertStatus::OPEN,
                AlertStatus::ACKNOWLEDGED,
            ], true)
        ) {
            return response()->json([
                'message' => 'Only active alerts can be resolved.',
            ], 409);
        }

        $alert->update(['status' => AlertStatus::RESOLVED, 'resolved_at' => now()]);

        return response()->json(
            $alert->fresh(),
            200
        );
    }
}
