<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;

class PlatformOwnerUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'owner@example.com'],
            [
                'tenant_id' => 0,
                'name' => 'Platform Owner',
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'no_wa' => '6200000000000',
                'kirim_notif_wa' => 'No',
            ]
        );

        $user->update([
            'tenant_id' => 0,
            'name' => 'Platform Owner',
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId(0);
        $role = Role::findOrCreate('Platform Owner', 'web');
        $user->syncRoles([$role]);
        app(PermissionRegistrar::class)->setPermissionsTeamId(1);
    }
}

