<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class SetPlatformPermissionTenant
{
    public function handle(Request $request, Closure $next)
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(0);
        return $next($request);
    }
}
