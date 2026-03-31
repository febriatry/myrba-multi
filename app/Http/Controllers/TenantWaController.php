<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\WaMessageStatusLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TenantWaController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:Super Admin']);
    }

    public function settings()
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $tenant = Tenant::query()->with('plan')->findOrFail($tenantId);

        return view('tenant.wa-settings', compact('tenant'));
    }

    public function updateSettings(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $tenant = Tenant::query()->findOrFail($tenantId);

        $validated = $request->validate([
            'wa_provider_mode' => 'required|in:app,manual,owner,tenant,developer',
            'wa_ivosight_base_url' => 'nullable|string|max:255',
            'wa_ivosight_api_key' => 'nullable|string|max:255',
            'wa_ivosight_sender_id' => 'nullable|string|max:100',
        ]);

        $mode = (string) $validated['wa_provider_mode'];
        if (in_array($mode, ['manual', 'tenant'], true)) {
            if (trim((string) ($validated['wa_ivosight_base_url'] ?? '')) === '' || trim((string) ($validated['wa_ivosight_api_key'] ?? '')) === '') {
                return redirect()->back()->withInput()->with('error', 'Base URL dan API Key wajib diisi jika memilih API sendiri.');
            }
        }

        $normalizedMode = in_array($mode, ['manual', 'tenant'], true) ? 'tenant' : 'owner';
        $tenant->update([
            'wa_provider_mode' => $normalizedMode,
            'wa_ivosight_base_url' => $normalizedMode === 'tenant' ? trim((string) ($validated['wa_ivosight_base_url'] ?? '')) : null,
            'wa_ivosight_api_key' => $normalizedMode === 'tenant' ? trim((string) ($validated['wa_ivosight_api_key'] ?? '')) : null,
            'wa_ivosight_sender_id' => $normalizedMode === 'tenant' ? trim((string) ($validated['wa_ivosight_sender_id'] ?? '')) : null,
        ]);

        return redirect()->route('tenant.wa.settings')->with('success', 'Pengaturan WhatsApp tenant berhasil disimpan.');
    }

    public function report(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $month = trim((string) $request->query('month', now()->format('Y-m')));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        $start = $month.'-01 00:00:00';
        $end = date('Y-m-t', strtotime($start)).' 23:59:59';

        $base = WaMessageStatusLog::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'sent')
            ->whereBetween('status_at', [$start, $end]);

        $byType = (clone $base)
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->orderByDesc('total')
            ->get();

        $total = (int) (clone $base)->count();

        $tenant = Tenant::query()->with('plan')->find($tenantId);
        $price = 0;
        $planQuota = is_array($tenant?->plan?->quota_json) ? $tenant->plan->quota_json : [];
        $tenantQuota = is_array($tenant?->quota_json) ? $tenant->quota_json : [];
        $merged = array_merge($planQuota, $tenantQuota);
        if (isset($merged['wa_price_per_message']) && is_numeric($merged['wa_price_per_message'])) {
            $price = (float) $merged['wa_price_per_message'];
        }
        $estimatedTotal = $price > 0 ? ($total * $price) : 0;

        return view('tenant.wa-report', compact('month', 'total', 'byType', 'price', 'estimatedTotal'));
    }
}
