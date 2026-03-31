<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TenantSuperAdminToolsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:Super Admin']);
    }

    public function syncPermissions(): RedirectResponse
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        if ($tenantId < 1) {
            return redirect()->back()->with('error', 'Tenant tidak valid.');
        }

        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();
        $registrar->setPermissionsTeamId($tenantId);

        $role = Role::findOrCreate('Super Admin', 'web');
        $allPermissions = Permission::all();
        $role->syncPermissions($allPermissions);

        $registrar->forgetCachedPermissions();

        return redirect()->back()->with('success', 'Permission Super Admin berhasil disinkronkan ('.$role->permissions()->count().'/'.$allPermissions->count().').');
    }
}
