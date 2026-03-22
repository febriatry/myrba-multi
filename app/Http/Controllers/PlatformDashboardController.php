<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PlatformDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'platform.team', 'role:Platform Owner']);
    }

    public function index()
    {
        $month = trim((string) request()->query('month', now()->format('Y-m')));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }
        $start = Carbon::parse($month . '-01')->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $tenantCount = (int) DB::table('tenants')->count();
        $tenantActiveCount = (int) DB::table('tenants')->where('status', 'active')->count();
        $tenantSuspendedCount = (int) DB::table('tenants')->where('status', 'suspended')->count();
        $planCount = (int) DB::table('tenant_plans')->count();
        $userCount = (int) DB::table('users')->count();

        $waMap = DB::table('wa_message_status_logs')
            ->select('tenant_id', DB::raw('COUNT(*) as total'))
            ->where('status', 'sent')
            ->whereBetween('status_at', [$start, $end])
            ->groupBy('tenant_id')
            ->pluck('total', 'tenant_id')
            ->toArray();

        $rows = DB::table('tenants')
            ->leftJoin('tenant_plans', 'tenant_plans.id', '=', 'tenants.plan_id')
            ->select(
                'tenants.id',
                'tenants.name',
                'tenants.code',
                'tenants.status',
                'tenants.plan_id',
                'tenants.features_json as tenant_features_json',
                'tenants.quota_json as tenant_quota_json',
                'tenant_plans.name as plan_name',
                'tenant_plans.code as plan_code',
                'tenant_plans.features_json as plan_features_json',
                'tenant_plans.quota_json as plan_quota_json'
            )
            ->orderBy('tenants.id', 'asc')
            ->get();

        $tenants = [];
        $totalWaSent = 0;
        $estimatedBillingTotal = 0.0;
        foreach ($rows as $r) {
            $planQuota = is_string($r->plan_quota_json) && trim($r->plan_quota_json) !== '' ? json_decode($r->plan_quota_json, true) : (is_array($r->plan_quota_json) ? $r->plan_quota_json : []);
            $tenantQuota = is_string($r->tenant_quota_json) && trim($r->tenant_quota_json) !== '' ? json_decode($r->tenant_quota_json, true) : (is_array($r->tenant_quota_json) ? $r->tenant_quota_json : []);
            $quota = array_merge(is_array($planQuota) ? $planQuota : [], is_array($tenantQuota) ? $tenantQuota : []);

            $planFeatures = is_string($r->plan_features_json) && trim($r->plan_features_json) !== '' ? json_decode($r->plan_features_json, true) : (is_array($r->plan_features_json) ? $r->plan_features_json : []);
            $tenantFeatures = is_string($r->tenant_features_json) && trim($r->tenant_features_json) !== '' ? json_decode($r->tenant_features_json, true) : (is_array($r->tenant_features_json) ? $r->tenant_features_json : []);
            $features = array_merge(is_array($planFeatures) ? $planFeatures : [], is_array($tenantFeatures) ? $tenantFeatures : []);

            $waEnabled = false;
            if (array_key_exists('whatsapp', $features)) {
                $waEnabled = (bool) $features['whatsapp'];
            }
            $waPrice = 0.0;
            if (isset($quota['wa_price_per_message']) && is_numeric($quota['wa_price_per_message'])) {
                $waPrice = (float) $quota['wa_price_per_message'];
            }
            $waMaxMonthly = null;
            if (isset($quota['max_wa_messages_monthly']) && is_numeric($quota['max_wa_messages_monthly'])) {
                $waMaxMonthly = (int) $quota['max_wa_messages_monthly'];
            }

            $sent = (int) ($waMap[$r->id] ?? 0);
            $totalWaSent += $sent;
            $estimated = $waPrice > 0 ? ($sent * $waPrice) : 0.0;
            $estimatedBillingTotal += $estimated;

            $tenants[] = [
                'id' => (int) $r->id,
                'name' => (string) $r->name,
                'code' => (string) $r->code,
                'status' => (string) $r->status,
                'plan_name' => (string) ($r->plan_name ?? '-'),
                'plan_code' => (string) ($r->plan_code ?? '-'),
                'wa_enabled' => $waEnabled,
                'wa_sent' => $sent,
                'wa_max_monthly' => $waMaxMonthly,
                'wa_price' => $waPrice,
                'wa_estimated_total' => $estimated,
            ];
        }

        return view('platform.dashboard', compact(
            'month',
            'tenantCount',
            'tenantActiveCount',
            'tenantSuspendedCount',
            'planCount',
            'userCount',
            'totalWaSent',
            'estimatedBillingTotal',
            'tenants'
        ));
    }
}
