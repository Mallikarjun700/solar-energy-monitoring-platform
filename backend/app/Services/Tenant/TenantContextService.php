<?php

namespace App\Services\Tenant;

use Illuminate\Http\Request;
use InvalidArgumentException;

class TenantContextService
{
    public function resolve(Request $request): string
    {
        $tenantId = $request->input('tenant_id');

        if (! is_string($tenantId) || ! preg_match(
            '/^[0-9a-fA-F-]{36}$/',
            $tenantId
        )) {
            throw new InvalidArgumentException(
                'A valid tenant_id is required.'
            );
        }

        return strtolower($tenantId);
    }

    public function resolveForUser(
        Request $request,
        bool $enforceRequestedTenant = true
    ): string {
        $user = $request->user();

        if (! $user) {
            throw new InvalidArgumentException(
                'Authenticated user is required.'
            );
        }

        $userTenantId = $user->tenant_id;

        if (
            ! is_string($userTenantId)
            || ! preg_match('/^[0-9a-fA-F-]{36}$/', $userTenantId)
        ) {
            throw new InvalidArgumentException(
                'Authenticated user is not assigned to a tenant.'
            );
        }

        $requestedTenantId = $request->input('tenant_id');

        if ($requestedTenantId !== null && $enforceRequestedTenant) {
            if (
                ! is_string($requestedTenantId)
                || ! preg_match(
                    '/^[0-9a-fA-F-]{36}$/',
                    $requestedTenantId
                )
            ) {
                throw new InvalidArgumentException(
                    'A valid tenant_id is required.'
                );
            }

            if (strtolower($requestedTenantId) !== strtolower($userTenantId)) {
                abort(403, 'Tenant access denied.');
            }
        }

        return strtolower($userTenantId);
    }
}
