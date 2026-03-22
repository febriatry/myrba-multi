<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CreateTenantSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;
        $email = 'tenant-superadmin@myrba.net';

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
        $role = Role::findOrCreate('Super Admin', 'web');

        $user = User::query()->where('email', $email)->first();
        if (!$user) {
            $user = User::query()->create([
                'tenant_id' => $tenantId,
                'name' => 'Tenant Super Admin',
                'email' => $email,
                'password' => bcrypt('Superadmin123!'),
                'no_wa' => '6200000000000',
                'kirim_notif_wa' => 'No',
            ]);
        } else {
            if ((int) $user->tenant_id !== $tenantId) {
                $user->tenant_id = $tenantId;
                $user->save();
            }
        }

        $user->assignRole($role);
    }
}

