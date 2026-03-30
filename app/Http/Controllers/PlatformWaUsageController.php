<?php

namespace App\Http\Controllers;

use App\Models\TenantPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlatformWaUsageController extends Controller
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

        $base = DB::table('wa_message_status_logs as l')
            ->where('l.status', 'sent')
            ->whereBetween('l.status_at', [$start, $end])
            ->where(function ($q) {
                $q->whereNull('l.billing_mode')->orWhere('l.billing_mode', 'owner');
            });

        $total = (int) (clone $base)->count();
        $byTenantRaw = (clone $base)
            ->leftJoin('tenants as t', 't.id', '=', 'l.tenant_id')
            ->select(
                'l.tenant_id',
                't.code as tenant_code',
                't.plan_id',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('l.tenant_id', 't.code', 't.plan_id')
            ->orderByDesc('total')
            ->get();

        $planIds = $byTenantRaw->pluck('plan_id')->filter()->unique()->values()->all();
        $plans = TenantPlan::query()->whereIn('id', $planIds)->get()->keyBy('id');

        $byTenant = $byTenantRaw->map(function ($r) use ($plans) {
            $plan = null;
            if (! empty($r->plan_id) && isset($plans[(int) $r->plan_id])) {
                $plan = $plans[(int) $r->plan_id];
            }
            $quota = is_array($plan?->quota_json) ? $plan->quota_json : [];
            $price = isset($quota['wa_price_per_message']) ? (float) $quota['wa_price_per_message'] : 0.0;
            $free = isset($quota['wa_free_messages_monthly']) ? (int) $quota['wa_free_messages_monthly'] : 0;
            $sent = (int) ($r->total ?? 0);
            $billable = max(0, $sent - max(0, $free));
            $amount = $billable * $price;

            return (object) [
                'tenant_id' => (int) ($r->tenant_id ?? 0),
                'tenant_code' => $r->tenant_code,
                'plan_name' => $plan?->name,
                'sent' => $sent,
                'free' => $free,
                'price' => $price,
                'billable' => $billable,
                'amount' => $amount,
            ];
        });

        $totalAmount = (float) $byTenant->sum('amount');

        return view('platform.wa-usage', compact('month', 'total', 'totalAmount', 'byTenant'));
    }
}
