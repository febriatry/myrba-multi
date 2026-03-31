<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\TenantEntitlementService;
use Illuminate\Support\Facades\DB;

class TenantAdminDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:Super Admin']);
    }

    public function index()
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $tenant = Tenant::query()->with('plan')->findOrFail($tenantId);
        $userCount = (int) DB::table('users')->where('tenant_id', $tenantId)->count();
        $pelangganCount = (int) DB::table('pelanggans')->where('tenant_id', $tenantId)->count();
        $waMonth = now()->format('Y-m');
        $waStart = $waMonth.'-01 00:00:00';
        $waEnd = date('Y-m-t', strtotime($waStart)).' 23:59:59';
        $waSentCount = (int) DB::table('wa_message_status_logs')
            ->where('tenant_id', $tenantId)
            ->where('status', 'sent')
            ->whereBetween('status_at', [$waStart, $waEnd])
            ->count();

        $waByBillingMode = DB::table('wa_message_status_logs')
            ->select('billing_mode', DB::raw('COUNT(*) as total'))
            ->where('tenant_id', $tenantId)
            ->where('status', 'sent')
            ->whereBetween('status_at', [$waStart, $waEnd])
            ->groupBy('billing_mode')
            ->get();
        $waBillingModeMap = [];
        foreach ($waByBillingMode as $r) {
            $key = $r->billing_mode !== null && $r->billing_mode !== '' ? (string) $r->billing_mode : 'owner';
            $waBillingModeMap[$key] = (int) $r->total;
        }
        $waSentOwner = (int) ($waBillingModeMap['owner'] ?? 0);
        $waSentTenant = (int) ($waBillingModeMap['tenant'] ?? 0);

        $planQuota = is_array($tenant->plan?->quota_json) ? $tenant->plan->quota_json : [];
        $tenantQuota = is_array($tenant->quota_json) ? $tenant->quota_json : [];
        $quota = array_merge($planQuota, $tenantQuota);

        $maxUsers = isset($quota['max_users']) && is_numeric($quota['max_users']) ? (int) $quota['max_users'] : null;
        $maxPelanggan = isset($quota['max_pelanggans']) && is_numeric($quota['max_pelanggans']) ? (int) $quota['max_pelanggans'] : null;
        $maxWaMessages = isset($quota['max_wa_messages_monthly']) && is_numeric($quota['max_wa_messages_monthly']) ? (int) $quota['max_wa_messages_monthly'] : null;

        $waFreeMonthly = isset($quota['wa_free_messages_monthly']) && is_numeric($quota['wa_free_messages_monthly']) ? (int) $quota['wa_free_messages_monthly'] : 0;
        $waPrice = isset($quota['wa_price_per_message']) && is_numeric($quota['wa_price_per_message']) ? (float) $quota['wa_price_per_message'] : 0.0;
        $waBillableOwner = max(0, $waSentOwner - max(0, $waFreeMonthly));
        $waEstimatedCost = $waPrice > 0 ? ($waBillableOwner * $waPrice) : 0.0;

        $waEnabled = TenantEntitlementService::featureEnabledForTenantId($tenantId, 'whatsapp', false);
        $paymentEnabled = TenantEntitlementService::featureEnabledForTenantId($tenantId, 'payment_gateway', false);

        $waProviderMode = strtolower((string) ($tenant->wa_provider_mode ?? 'developer'));
        $waProviderLabel = $waProviderMode === 'tenant' ? 'WA Tenant (API sendiri)' : 'WA Owner';
        $tripayProviderMode = strtolower((string) ($tenant->tripay_provider_mode ?? 'owner'));
        $tripayProviderLabel = $tripayProviderMode === 'tenant' ? 'Tripay Tenant (API sendiri)' : 'Tripay Owner';

        $waRemaining = $maxWaMessages ? max(0, $maxWaMessages - (int) $waSentCount) : null;

        return view('tenant.dashboard', compact(
            'tenant',
            'userCount',
            'pelangganCount',
            'waMonth',
            'waSentCount',
            'waSentOwner',
            'waSentTenant',
            'maxUsers',
            'maxPelanggan',
            'maxWaMessages',
            'waRemaining',
            'waEnabled',
            'paymentEnabled',
            'waProviderLabel',
            'tripayProviderLabel',
            'waFreeMonthly',
            'waPrice',
            'waBillableOwner',
            'waEstimatedCost'
        ));
    }
}
