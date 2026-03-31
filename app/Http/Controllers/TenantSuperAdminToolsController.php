<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        $created = 0;
        $definitions = config('permission.permissions', []);
        foreach ($definitions as $def) {
            foreach (($def['access'] ?? []) as $name) {
                $name = trim((string) $name);
                if ($name === '') {
                    continue;
                }
                $row = Permission::firstOrCreate([
                    'name' => $name,
                    'guard_name' => 'web',
                ]);
                if ($row->wasRecentlyCreated) {
                    $created++;
                }
            }
        }
        if (Schema::hasTable('area_coverages')) {
            $extra = ['area coverage access:all'];
            foreach ($extra as $name) {
                $row = Permission::firstOrCreate([
                    'name' => $name,
                    'guard_name' => 'web',
                ]);
                if ($row->wasRecentlyCreated) {
                    $created++;
                }
            }
            $areaIds = DB::table('area_coverages')->pluck('id')->map(fn ($v) => (int) $v)->all();
            foreach ($areaIds as $id) {
                $row = Permission::firstOrCreate([
                    'name' => 'area coverage access:'.$id,
                    'guard_name' => 'web',
                ]);
                if ($row->wasRecentlyCreated) {
                    $created++;
                }
            }
        }

        $role = Role::findOrCreate('Super Admin', 'web');
        $allPermissions = Permission::all();
        $role->syncPermissions($allPermissions);

        $registrar->forgetCachedPermissions();

        return redirect()->back()->with('success', 'Permission Super Admin berhasil disinkronkan ('.$role->permissions()->count().'/'.$allPermissions->count().'). New permissions: '.$created.'.');
    }
}
