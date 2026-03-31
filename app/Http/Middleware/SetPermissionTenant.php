<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SetPermissionTenant
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $tenantId = $user ? (int) ($user->tenant_id ?? 0) : 0;

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId > 0 ? $tenantId : null);
        if ($tenantId > 0 && $user && $user->hasRole('Super Admin')) {
            $registrar = app(PermissionRegistrar::class);
            $registrar->forgetCachedPermissions();
            $role = Role::findOrCreate('Super Admin', 'web');
            $totalPermissions = (int) Permission::query()->count();
            $rolePermissions = (int) $role->permissions()->count();
            if ($rolePermissions < $totalPermissions) {
                $role->syncPermissions(Permission::all());
                $registrar->forgetCachedPermissions();
            }
        }

        return $next($request);
    }
}
