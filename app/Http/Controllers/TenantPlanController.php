<?php

namespace App\Http\Controllers;

use App\Models\TenantPlan;
use Illuminate\Http\Request;

class TenantPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'platform.team', 'role:Platform Owner']);
    }

    public function index()
    {
        $plans = TenantPlan::query()->orderBy('id')->get();

        return view('platform.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('platform.plans.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:50|alpha_dash|unique:tenant_plans,code',
            'status' => 'required|string|in:active,inactive',
            'feature_whatsapp' => 'nullable|in:1,0',
            'feature_payment_gateway' => 'nullable|in:1,0',
            'feature_inventory' => 'nullable|in:1,0',
            'feature_hr' => 'nullable|in:1,0',
            'max_users' => 'nullable|integer|min:1',
            'max_pelanggans' => 'nullable|integer|min:1',
            'max_wa_messages_monthly' => 'nullable|integer|min:1',
            'wa_free_messages_monthly' => 'nullable|integer|min:0',
            'wa_price_per_message' => 'nullable|numeric|min:0',
        ]);

        $features = [
            'whatsapp' => isset($validated['feature_whatsapp']) ? ((int) $validated['feature_whatsapp'] === 1) : false,
            'payment_gateway' => isset($validated['feature_payment_gateway']) ? ((int) $validated['feature_payment_gateway'] === 1) : false,
            'inventory' => isset($validated['feature_inventory']) ? ((int) $validated['feature_inventory'] === 1) : false,
            'hr' => isset($validated['feature_hr']) ? ((int) $validated['feature_hr'] === 1) : false,
        ];

        $quota = [];
        foreach (['max_users', 'max_pelanggans', 'max_wa_messages_monthly'] as $k) {
            if (isset($validated[$k]) && is_numeric($validated[$k])) {
                $quota[$k] = (int) $validated[$k];
            }
        }
        if (isset($validated['wa_free_messages_monthly']) && is_numeric($validated['wa_free_messages_monthly'])) {
            $quota['wa_free_messages_monthly'] = (int) $validated['wa_free_messages_monthly'];
        }
        if (isset($validated['wa_price_per_message']) && is_numeric($validated['wa_price_per_message'])) {
            $quota['wa_price_per_message'] = (float) $validated['wa_price_per_message'];
        }

        TenantPlan::query()->create([
            'name' => trim((string) $validated['name']),
            'code' => trim((string) $validated['code']),
            'status' => (string) $validated['status'],
            'features_json' => $features,
            'quota_json' => $quota,
        ]);

        return redirect()->route('platform.plans.index')->with('success', 'Paket tenant berhasil dibuat.');
    }

    public function edit(TenantPlan $plan)
    {
        return view('platform.plans.edit', compact('plan'));
    }

    public function update(Request $request, TenantPlan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:50|alpha_dash|unique:tenant_plans,code,'.$plan->id,
            'status' => 'required|string|in:active,inactive',
            'feature_whatsapp' => 'nullable|in:1,0',
            'feature_payment_gateway' => 'nullable|in:1,0',
            'feature_inventory' => 'nullable|in:1,0',
            'feature_hr' => 'nullable|in:1,0',
            'max_users' => 'nullable|integer|min:1',
            'max_pelanggans' => 'nullable|integer|min:1',
            'max_wa_messages_monthly' => 'nullable|integer|min:1',
            'wa_free_messages_monthly' => 'nullable|integer|min:0',
            'wa_price_per_message' => 'nullable|numeric|min:0',
        ]);

        $features = [
            'whatsapp' => isset($validated['feature_whatsapp']) ? ((int) $validated['feature_whatsapp'] === 1) : false,
            'payment_gateway' => isset($validated['feature_payment_gateway']) ? ((int) $validated['feature_payment_gateway'] === 1) : false,
            'inventory' => isset($validated['feature_inventory']) ? ((int) $validated['feature_inventory'] === 1) : false,
            'hr' => isset($validated['feature_hr']) ? ((int) $validated['feature_hr'] === 1) : false,
        ];

        $quota = [];
        foreach (['max_users', 'max_pelanggans', 'max_wa_messages_monthly'] as $k) {
            if (isset($validated[$k]) && is_numeric($validated[$k])) {
                $quota[$k] = (int) $validated[$k];
            }
        }
        if (isset($validated['wa_free_messages_monthly']) && is_numeric($validated['wa_free_messages_monthly'])) {
            $quota['wa_free_messages_monthly'] = (int) $validated['wa_free_messages_monthly'];
        }
        if (isset($validated['wa_price_per_message']) && is_numeric($validated['wa_price_per_message'])) {
            $quota['wa_price_per_message'] = (float) $validated['wa_price_per_message'];
        }

        $plan->update([
            'name' => trim((string) $validated['name']),
            'code' => trim((string) $validated['code']),
            'status' => (string) $validated['status'],
            'features_json' => $features,
            'quota_json' => $quota,
        ]);

        return redirect()->route('platform.plans.index')->with('success', 'Paket tenant berhasil diupdate.');
    }

    public function destroy(TenantPlan $plan)
    {
        $plan->delete();

        return redirect()->route('platform.plans.index')->with('success', 'Paket tenant berhasil dihapus.');
    }
}
