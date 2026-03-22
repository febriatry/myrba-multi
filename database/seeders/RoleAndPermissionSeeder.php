<?php

namespace Database\Seeders;

use App\Models\AreaCoverage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\{Role, Permission};
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(PermissionRegistrar::class)->setPermissionsTeamId(1);

        // Buat permission baru jika belum ada
        foreach (config('permission.permissions') as $permission) {
            foreach ($permission['access'] as $access) {
                Permission::firstOrCreate(['name' => $access, 'guard_name' => 'web']);
            }
        }

        Permission::firstOrCreate(['name' => 'area coverage access:all', 'guard_name' => 'web']);
        $areaIds = AreaCoverage::pluck('id')->map(fn ($v) => (int) $v)->all();
        foreach ($areaIds as $id) {
            Permission::firstOrCreate(['name' => 'area coverage access:' . $id, 'guard_name' => 'web']);
        }

        // Ambil role Super Admin
        $superAdmin = Role::findOrCreate('Super Admin', 'web');
        
        // Sync semua permission yang ada di database ke Super Admin
        $superAdmin->syncPermissions(Permission::all());

        $mikrotikAdmin = Role::findOrCreate('Mikrotik Admin', 'web');
        $mikrotikAdmin->syncPermissions(Permission::whereIn('name', [
            'settingmikrotik view',
            'settingmikrotik create',
            'settingmikrotik edit',
            'settingmikrotik delete',
            'statusrouter view',
            'log view',
            'dhcp view',
            'interface view',
            'mikrotik automation view',
            'mikrotik automation manage',
            'mikrotik automation execute',
            'mikrotik automation log view',
            'audit pelanggan view',
            'audit pelanggan export',
        ])->get());

        $mikrotikOperator = Role::findOrCreate('Mikrotik Operator', 'web');
        $mikrotikOperator->syncPermissions(Permission::whereIn('name', [
            'statusrouter view',
            'log view',
            'dhcp view',
            'interface view',
            'mikrotik automation view',
            'mikrotik automation execute',
            'mikrotik automation log view',
            'audit pelanggan view',
        ])->get());

        $networkAuditor = Role::findOrCreate('Network Auditor', 'web');
        $networkAuditor->syncPermissions(Permission::whereIn('name', [
            'audit pelanggan view',
            'audit pelanggan export',
            'statusrouter view',
            'log view',
            'active ppp view',
            'non active ppp view',
        ])->get());

        app(PermissionRegistrar::class)->setPermissionsTeamId(0);
        $platformRole = Role::findOrCreate('Platform Owner', 'web');
        app(PermissionRegistrar::class)->setPermissionsTeamId(1);

        $userAdmin = User::first();
        if ($userAdmin) {
            $userAdmin->assignRole('Super Admin');
        }

        $platformUser = User::query()->where('email', 'febri@myrba.net')->first() ?: $userAdmin;
        if ($platformUser) {
            app(PermissionRegistrar::class)->setPermissionsTeamId(0);
            $platformUser->assignRole($platformRole);
            app(PermissionRegistrar::class)->setPermissionsTeamId(1);
        }
    }
}
