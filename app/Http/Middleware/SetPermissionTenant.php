<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class SetPermissionTenant
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $tenantId = $user ? (int) ($user->tenant_id ?? 0) : 0;

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId > 0 ? $tenantId : null);

        return $next($request);
    }
}

