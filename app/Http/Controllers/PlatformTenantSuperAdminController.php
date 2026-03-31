<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PlatformTenantSuperAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'platform.team', 'role:Platform Owner']);
    }

    public function index(Tenant $tenant)
    {
        $tenantId = (int) $tenant->id;

        $admins = DB::table('model_has_roles as m')
            ->join('roles as r', 'r.id', '=', 'm.role_id')
            ->join('users as u', 'u.id', '=', 'm.model_id')
            ->where('m.tenant_id', $tenantId)
            ->where('m.model_type', User::class)
            ->where('r.name', 'Super Admin')
            ->select('u.id', 'u.name', 'u.email', 'u.no_wa', 'u.created_at')
            ->orderBy('u.id')
            ->get();

        return view('platform.tenants.super-admins', [
            'tenant' => $tenant,
            'admins' => $admins,
        ]);
    }

    public function store(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:190|unique:users,email',
            'no_wa' => 'required|string|max:15',
            'password' => 'required|string|min:6',
        ]);

        $tenantId = (int) $tenant->id;

        DB::transaction(function () use ($validated, $tenantId) {
            $user = User::query()->create([
                'tenant_id' => $tenantId,
                'name' => trim((string) $validated['name']),
                'email' => trim((string) $validated['email']),
                'no_wa' => trim((string) $validated['no_wa']),
                'kirim_notif_wa' => 'No',
                'password' => bcrypt((string) $validated['password']),
            ]);

            app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
            $role = Role::findOrCreate('Super Admin', 'web');
            $user->assignRole($role);
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        });

        return redirect()->route('platform.tenants.super-admins.index', $tenantId)->with('success', 'Super Admin tenant berhasil ditambahkan.');
    }

    public function destroy(Tenant $tenant, int $userId)
    {
        $tenantId = (int) $tenant->id;
        $user = User::query()->where('tenant_id', $tenantId)->findOrFail($userId);

        $count = (int) DB::table('model_has_roles as m')
            ->join('roles as r', 'r.id', '=', 'm.role_id')
            ->where('m.tenant_id', $tenantId)
            ->where('m.model_type', User::class)
            ->where('r.name', 'Super Admin')
            ->distinct()
            ->count('m.model_id');

        if ($count <= 1) {
            return redirect()->back()->with('error', 'Minimal harus ada 1 Super Admin di tenant.');
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
        $role = Role::findOrCreate('Super Admin', 'web');
        $user->removeRole($role);
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        return redirect()->back()->with('success', 'Role Super Admin berhasil dihapus dari user.');
    }
}
