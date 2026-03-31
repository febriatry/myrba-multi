<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlatformOwnerDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'platform.team', 'role:Platform Owner']);
    }

    public function index(Request $request)
    {
        $month = trim((string) $request->query('month', now()->format('Y-m')));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }
        $start = Carbon::parse($month.'-01')->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $plans = TenantPlan::query()->orderBy('id')->get();
        $planById = $plans->keyBy('id');

        $tenants = Tenant::query()->select('id', 'plan_id')->get();
        $tenantPlanMap = [];
        $tenantCountByPlan = [];
        foreach ($tenants as $t) {
            $tenantPlanMap[(int) $t->id] = (int) ($t->plan_id ?? 0);
            $pid = (int) ($t->plan_id ?? 0);
            $tenantCountByPlan[$pid] = ($tenantCountByPlan[$pid] ?? 0) + 1;
        }

        $waOwnerBase = DB::table('wa_message_status_logs as l')
            ->where('l.status', 'sent')
            ->whereBetween('l.status_at', [$start, $end])
            ->where(function ($q) {
                $q->whereNull('l.billing_mode')->orWhere('l.billing_mode', 'owner');
            });

        $waOwnerTotalSent = (int) (clone $waOwnerBase)->count();
        $waByTenant = (clone $waOwnerBase)
            ->select('l.tenant_id', DB::raw('COUNT(*) as total'))
            ->groupBy('l.tenant_id')
            ->get();

        $totalAmount = 0.0;
        $totalBillable = 0;
        $planAgg = [];
        foreach ($waByTenant as $row) {
            $tenantId = (int) ($row->tenant_id ?? 0);
            $sent = (int) ($row->total ?? 0);
            if ($tenantId < 1) {
                continue;
            }
            $planId = (int) ($tenantPlanMap[$tenantId] ?? 0);
            $plan = $planId > 0 ? ($planById[$planId] ?? null) : null;
            $quota = is_array($plan?->quota_json) ? $plan->quota_json : [];
            $price = isset($quota['wa_price_per_message']) && is_numeric($quota['wa_price_per_message']) ? (float) $quota['wa_price_per_message'] : 0.0;
            $free = isset($quota['wa_free_messages_monthly']) && is_numeric($quota['wa_free_messages_monthly']) ? (int) $quota['wa_free_messages_monthly'] : 0;
            $billable = max(0, $sent - max(0, $free));
            $amount = $price > 0 ? ($billable * $price) : 0.0;

            $totalBillable += $billable;
            $totalAmount += $amount;

            if (! isset($planAgg[$planId])) {
                $planAgg[$planId] = [
                    'plan_id' => $planId,
                    'plan_name' => $plan?->name ?? '-',
                    'tenant_count' => (int) ($tenantCountByPlan[$planId] ?? 0),
                    'wa_sent' => 0,
                    'wa_billable' => 0,
                    'wa_price' => $price,
                    'wa_free' => $free,
                    'amount' => 0.0,
                ];
            }
            $planAgg[$planId]['wa_sent'] += $sent;
            $planAgg[$planId]['wa_billable'] += $billable;
            $planAgg[$planId]['amount'] += $amount;
        }

        $planRows = array_values($planAgg);
        usort($planRows, function ($a, $b) {
            return ($b['amount'] <=> $a['amount']) ?: ($b['wa_sent'] <=> $a['wa_sent']);
        });

        return view('platform.owner-dashboard', [
            'month' => $month,
            'waOwnerTotalSent' => $waOwnerTotalSent,
            'waOwnerTotalBillable' => $totalBillable,
            'waOwnerTotalAmount' => $totalAmount,
            'planRows' => $planRows,
        ]);
    }
}
