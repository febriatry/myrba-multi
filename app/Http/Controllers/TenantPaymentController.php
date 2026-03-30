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
            'tripay_provider_mode' => 'required|in:owner,tenant',
            'tripay_base_url' => 'nullable|string|max:255',
            'tripay_api_key' => 'nullable|string|max:255',
            'tripay_merchant_code' => 'nullable|string|max:100',
            'tripay_private_key' => 'nullable|string|max:255',
        ]);

        if ($validated['tripay_provider_mode'] === 'tenant') {
            $missing = [];
            foreach (['tripay_base_url', 'tripay_api_key', 'tripay_merchant_code', 'tripay_private_key'] as $k) {
                if (empty($validated[$k])) {
                    $missing[] = $k;
                }
            }
            if (! empty($missing)) {
                return back()->with('error', 'Konfigurasi Tripay tenant belum lengkap: '.implode(', ', $missing))->withInput();
            }
        }

        $tenant->update([
            'tripay_provider_mode' => (string) $validated['tripay_provider_mode'],
            'tripay_base_url' => ! empty($validated['tripay_base_url']) ? rtrim(trim((string) $validated['tripay_base_url']), '/').'/' : null,
            'tripay_api_key' => ! empty($validated['tripay_api_key']) ? trim((string) $validated['tripay_api_key']) : null,
            'tripay_merchant_code' => ! empty($validated['tripay_merchant_code']) ? trim((string) $validated['tripay_merchant_code']) : null,
            'tripay_private_key' => ! empty($validated['tripay_private_key']) ? trim((string) $validated['tripay_private_key']) : null,
        ]);

        return redirect()->route('tenant.payment.settings')->with('success', 'Pengaturan payment gateway berhasil disimpan.');
    }
}
