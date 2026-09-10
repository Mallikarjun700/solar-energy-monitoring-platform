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
            throw new InvalidArgumentException('A valid tenant_id is required.');
        }

        return strtolower($tenantId);
    }
}
