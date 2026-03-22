<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantPaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:Super Admin', 'tenant.feature:payment_gateway']);
    }

    public function settings()
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $tenant = Tenant::query()->with('plan')->findOrFail($tenantId);
        return view('tenant.payment-settings', compact('tenant'));
    }

    public function updateSettings(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $tenant = Tenant::query()->findOrFail($tenantId);

        $validated = $request->validate([
            'tripay_base_url' => 'required|string|max:255',
            'tripay_api_key' => 'required|string|max:255',
            'tripay_merchant_code' => 'required|string|max:100',
            'tripay_private_key' => 'required|string|max:255',
        ]);

        $tenant->update([
            'tripay_base_url' => rtrim(trim((string) $validated['tripay_base_url']), '/') . '/',
            'tripay_api_key' => trim((string) $validated['tripay_api_key']),
            'tripay_merchant_code' => trim((string) $validated['tripay_merchant_code']),
            'tripay_private_key' => trim((string) $validated['tripay_private_key']),
        ]);

        return redirect()->route('tenant.payment.settings')->with('success', 'Pengaturan payment gateway berhasil disimpan.');
    }
}

