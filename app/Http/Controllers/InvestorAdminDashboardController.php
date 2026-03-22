<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvestorAdminDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'web', 'permission:investor rule manage'])->only('index');
    }

    public function index(Request $request)
    {
        $period = trim((string) $request->query('period', now()->format('Y-m')));

        $minStartPeriod = DB::table('investor_share_rules')
            ->whereNotNull('start_period')
            ->where('start_period', '<>', '')
            ->min('start_period');
        $minStartPeriod = $minStartPeriod ? trim((string) $minStartPeriod) : null;

        $periodOptions = [];
        try {
            $end = Carbon::now()->startOfMonth();
            $start = $minStartPeriod !== null
                ? Carbon::createFromFormat('Y-m', $minStartPeriod)->startOfMonth()
                : $end->copy()->subMonths(23);
            if ($start->greaterThan($end)) {
                $start = $end->copy();
            }
            $cursor = $end->copy();
            while ($cursor->greaterThanOrEqualTo($start)) {
                $periodOptions[] = $cursor->format('Y-m');
                $cursor->subMonth();
            }
        } catch (\Throwable $e) {
            $periodOptions = [Carbon::now()->format('Y-m')];
        }
        if (!in_array($period, $periodOptions, true)) {
            $period = $periodOptions[0] ?? Carbon::now()->format('Y-m');
        }

        $userIds = array_values(array_unique(array_merge(
            DB::table('investor_share_rules')->pluck('user_id')->map(fn ($v) => (int) $v)->all(),
            DB::table('investor_wallets')->pluck('user_id')->map(fn ($v) => (int) $v)->all(),
            DB::table('investor_payout_requests')->pluck('user_id')->map(fn ($v) => (int) $v)->all(),
            DB::table('investor_earnings')->pluck('user_id')->map(fn ($v) => (int) $v)->all()
        )));

        $users = [];
        if (!empty($userIds)) {
            $users = DB::table('users')
                ->select('id', 'name', 'email')
                ->whereIn('id', $userIds)
                ->orderBy('name')
                ->get();
        }

        $wallets = DB::table('investor_wallets')->select('user_id', 'balance')->get()->keyBy('user_id');

        $rulesAgg = DB::table('investor_share_rules')
            ->select('user_id', DB::raw('COUNT(*) as rules_total'), DB::raw("SUM(CASE WHEN is_aktif='Yes' THEN 1 ELSE 0 END) as rules_active"))
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $earnPeriod = DB::table('investor_earnings')
            ->select('user_id', DB::raw('COUNT(*) as earning_count'), DB::raw('SUM(amount) as earning_amount'))
            ->where('periode', $period)
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $earnTotal = DB::table('investor_earnings')
            ->select('user_id', DB::raw('COUNT(*) as earning_total_count'), DB::raw('SUM(amount) as earning_total_amount'))
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $payoutPending = DB::table('investor_payout_requests')
            ->select('user_id', DB::raw('COUNT(*) as pending_count'), DB::raw('SUM(amount) as pending_amount'))
            ->where('status', 'Pending')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $payoutApprovedPeriod = DB::table('investor_payout_requests')
            ->select('user_id', DB::raw('COUNT(*) as approved_count'), DB::raw('SUM(amount) as approved_amount'))
            ->where('status', 'Approved')
            ->whereNotNull('approved_at')
            ->whereRaw('LEFT(approved_at, 7) = ?', [$period])
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $lastActivity = DB::table('investor_wallet_histories')
            ->select('user_id', DB::raw('MAX(created_at) as last_activity_at'))
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $rows = [];
        $summary = [
            'total_users' => 0,
            'total_balance' => 0.0,
            'earning_period_amount' => 0.0,
            'payout_pending_amount' => 0.0,
            'payout_approved_period_amount' => 0.0,
        ];

        foreach ($users as $u) {
            $uid = (int) $u->id;
            $balance = (float) (data_get($wallets, $uid . '.balance') ?? 0);
            $rulesTotal = (int) (data_get($rulesAgg, $uid . '.rules_total') ?? 0);
            $rulesActive = (int) (data_get($rulesAgg, $uid . '.rules_active') ?? 0);
            $earningCount = (int) (data_get($earnPeriod, $uid . '.earning_count') ?? 0);
            $earningAmount = (float) (data_get($earnPeriod, $uid . '.earning_amount') ?? 0);
            $earningTotalAmount = (float) (data_get($earnTotal, $uid . '.earning_total_amount') ?? 0);
            $pendingCount = (int) (data_get($payoutPending, $uid . '.pending_count') ?? 0);
            $pendingAmount = (float) (data_get($payoutPending, $uid . '.pending_amount') ?? 0);
            $approvedPeriodAmount = (float) (data_get($payoutApprovedPeriod, $uid . '.approved_amount') ?? 0);
            $last = (string) (data_get($lastActivity, $uid . '.last_activity_at') ?? '');

            $rows[] = [
                'id' => $uid,
                'name' => $u->name,
                'email' => $u->email,
                'balance' => $balance,
                'rules_total' => $rulesTotal,
                'rules_active' => $rulesActive,
                'earning_period_count' => $earningCount,
                'earning_period_amount' => $earningAmount,
                'earning_total_amount' => $earningTotalAmount,
                'payout_pending_count' => $pendingCount,
                'payout_pending_amount' => $pendingAmount,
                'payout_approved_period_amount' => $approvedPeriodAmount,
                'last_activity_at' => $last,
            ];

            $summary['total_users']++;
            $summary['total_balance'] += $balance;
            $summary['earning_period_amount'] += $earningAmount;
            $summary['payout_pending_amount'] += $pendingAmount;
            $summary['payout_approved_period_amount'] += $approvedPeriodAmount;
        }

        return view('investor-admin.index', compact('rows', 'summary', 'period', 'periodOptions', 'minStartPeriod'));
    }
}

