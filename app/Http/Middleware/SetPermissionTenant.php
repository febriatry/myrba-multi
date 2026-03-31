<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SetPermissionTenant
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $tenantId = $user ? (int) ($user->tenant_id ?? 0) : 0;

        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($tenantId > 0 ? $tenantId : null);
        if ($tenantId > 0 && $user && $user->hasRole('Super Admin')) {
            $registrar->forgetCachedPermissions();

            $role = Role::findOrCreate('Super Admin', 'web');
            $permCount = (int) Permission::query()->count();
            $rolePermCount = (int) $role->permissions()->count();
            $cacheKey = 'super_admin_perm_sync:tenant:'.$tenantId.':count';
            $lastCount = (int) Cache::get($cacheKey, 0);

            if ($rolePermCount < $permCount || $lastCount !== $permCount) {
                $role->syncPermissions(Permission::all());
                $registrar->forgetCachedPermissions();
                Cache::put($cacheKey, $permCount, now()->addDays(7));
            }
        }

        return $next($request);
    }
}
