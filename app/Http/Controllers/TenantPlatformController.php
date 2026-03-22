<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class TenantPlatformController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'platform.team', 'role:Platform Owner']);
    }

    public function index()
    {
        $tenants = Tenant::query()->with('plan')->orderBy('id')->get();
        return view('platform.tenants.index', compact('tenants'));
    }

    public function create()
    {
        $plans = TenantPlan::query()->where('status', 'active')->orderBy('id')->get();
        return view('platform.tenants.create', compact('plans'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:50|alpha_dash|unique:tenants,code',
            'status' => 'required|string|in:active,suspended',
            'plan_id' => 'required|integer|exists:tenant_plans,id',
            'admin_name' => 'required|string|max:150',
            'admin_email' => 'required|email|max:190|unique:users,email',
            'admin_password' => 'required|string|min:6',
        ]);

        $tenant = null;
        DB::transaction(function () use ($validated, &$tenant) {
            $tenant = Tenant::query()->create([
                'name' => trim((string) $validated['name']),
                'code' => trim((string) $validated['code']),
                'status' => (string) $validated['status'],
                'plan_id' => (int) $validated['plan_id'],
            ]);

            $user = User::query()->create([
                'tenant_id' => (int) $tenant->id,
                'name' => trim((string) $validated['admin_name']),
                'email' => trim((string) $validated['admin_email']),
                'password' => bcrypt((string) $validated['admin_password']),
                'no_wa' => '6200000000000',
                'kirim_notif_wa' => 'No',
            ]);

            app(PermissionRegistrar::class)->setPermissionsTeamId((int) $tenant->id);
            \Spatie\Permission\Models\Role::findOrCreate('Super Admin', 'web');
            $user->assignRole('Super Admin');
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        });

        return redirect()->route('platform.tenants.index')->with('success', 'Tenant berhasil dibuat.');
    }

    public function edit(Tenant $tenant)
    {
        $plans = TenantPlan::query()->orderBy('id')->get();
        return view('platform.tenants.edit', compact('tenant', 'plans'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:50|alpha_dash|unique:tenants,code,' . $tenant->id,
            'status' => 'required|string|in:active,suspended',
            'plan_id' => 'required|integer|exists:tenant_plans,id',
            'override_feature_whatsapp' => 'nullable|in:1,0',
            'override_feature_payment_gateway' => 'nullable|in:1,0',
            'override_feature_inventory' => 'nullable|in:1,0',
            'override_feature_hr' => 'nullable|in:1,0',
            'override_max_users' => 'nullable|integer|min:1',
            'override_max_pelanggans' => 'nullable|integer|min:1',
            'override_max_wa_messages_monthly' => 'nullable|integer|min:1',
            'override_wa_price_per_message' => 'nullable|numeric|min:0',
        ]);

        $features = [];
        $map = [
            'whatsapp' => 'override_feature_whatsapp',
            'payment_gateway' => 'override_feature_payment_gateway',
            'inventory' => 'override_feature_inventory',
            'hr' => 'override_feature_hr',
        ];
        foreach ($map as $key => $field) {
            if (array_key_exists($field, $validated) && $validated[$field] !== null && $validated[$field] !== '') {
                $features[$key] = ((int) $validated[$field]) === 1;
            }
        }

        $quota = [];
        if (isset($validated['override_max_users']) && is_numeric($validated['override_max_users'])) {
            $quota['max_users'] = (int) $validated['override_max_users'];
        }
        if (isset($validated['override_max_pelanggans']) && is_numeric($validated['override_max_pelanggans'])) {
            $quota['max_pelanggans'] = (int) $validated['override_max_pelanggans'];
        }
        if (isset($validated['override_max_wa_messages_monthly']) && is_numeric($validated['override_max_wa_messages_monthly'])) {
            $quota['max_wa_messages_monthly'] = (int) $validated['override_max_wa_messages_monthly'];
        }
        if (isset($validated['override_wa_price_per_message']) && is_numeric($validated['override_wa_price_per_message'])) {
            $quota['wa_price_per_message'] = (float) $validated['override_wa_price_per_message'];
        }

        $tenant->update([
            'name' => trim((string) $validated['name']),
            'code' => trim((string) $validated['code']),
            'status' => (string) $validated['status'],
            'plan_id' => (int) $validated['plan_id'],
            'features_json' => !empty($features) ? $features : null,
            'quota_json' => !empty($quota) ? $quota : null,
        ]);

        return redirect()->route('platform.tenants.index')->with('success', 'Tenant berhasil diupdate.');
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->delete();
        return redirect()->route('platform.tenants.index')->with('success', 'Tenant berhasil dihapus.');
    }
}
