<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvestorController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'web', 'permission:investor view'])->only('index');
    }

    public function index(Request $request)
    {
        $userId = (int) Auth::id();
        $requestedPeriod = trim((string) $request->query('period', now()->format('Y-m')));

        $wallet = DB::table('investor_wallets')->where('user_id', $userId)->first();
        $balance = (float) ($wallet->balance ?? 0);

        $rules = DB::table('investor_share_rules')
            ->where('user_id', $userId)
            ->where('is_aktif', 'Yes')
            ->get();

        $startPeriods = $rules
            ->pluck('start_period')
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '');
        $minStartPeriod = $startPeriods->isEmpty() ? null : (string) $startPeriods->min();

        $period = $requestedPeriod;
        if ($minStartPeriod !== null && $period !== '' && strcmp($period, $minStartPeriod) < 0) {
            $period = $minStartPeriod;
        }

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

        $manualPelangganByRule = [];
        if (DB::getSchemaBuilder()->hasTable('investor_share_rule_pelanggans')) {
            $ruleIds = $rules->pluck('id')->map(fn ($v) => (int) $v)->all();
            if (!empty($ruleIds)) {
                $rows = DB::table('investor_share_rule_pelanggans')
                    ->select('rule_id', 'pelanggan_id')
                    ->whereIn('rule_id', $ruleIds)
                    ->where('is_included', 'Yes')
                    ->get();
                foreach ($rows as $row) {
                    $rid = (int) $row->rule_id;
                    if (!isset($manualPelangganByRule[$rid])) {
                        $manualPelangganByRule[$rid] = [];
                    }
                    $manualPelangganByRule[$rid][] = (int) $row->pelanggan_id;
                }
            }
        }

        $pelangganIds = [];
        foreach ($rules as $rule) {
            $ruleId = (int) ($rule->id ?? 0);
            $manualList = $manualPelangganByRule[$ruleId] ?? [];
            if (!empty($manualList)) {
                $pelangganIds = array_merge($pelangganIds, $manualList);
                continue;
            }
            $q = DB::table('pelanggans')->select('id')->where('status_berlangganan', 'Aktif');
            if ($rule->rule_type === 'per_area' && !empty($rule->coverage_area_id)) {
                $q->where('coverage_area', (int) $rule->coverage_area_id);
            }
            if ($rule->rule_type === 'per_package' && !empty($rule->package_id)) {
                $q->where('paket_layanan', (int) $rule->package_id);
            }
            $pelangganIds = array_merge($pelangganIds, $q->pluck('id')->all());
        }
        $pelangganIds = array_values(array_unique(array_map('intval', $pelangganIds)));
        if (!empty($pelangganIds)) {
            $pelangganIds = DB::table('pelanggans')
                ->whereIn('id', $pelangganIds)
                ->where('status_berlangganan', 'Aktif')
                ->pluck('id')
                ->map(fn ($v) => (int) $v)
                ->all();
        }

        $pelanggans = [];
        $tagihansByPelanggan = [];
        if (!empty($pelangganIds)) {
            $pelanggans = DB::table('pelanggans')
                ->leftJoin('area_coverages', 'pelanggans.coverage_area', '=', 'area_coverages.id')
                ->leftJoin('packages', 'pelanggans.paket_layanan', '=', 'packages.id')
                ->select(
                    'pelanggans.id',
                    'pelanggans.nama',
                    'pelanggans.no_layanan',
                    'area_coverages.nama as area_nama',
                    'packages.nama_layanan as paket_nama'
                )
                ->whereIn('pelanggans.id', $pelangganIds)
                ->orderBy('pelanggans.no_layanan')
                ->get();

            $tagihans = DB::table('tagihans')
                ->select('id', 'pelanggan_id', 'no_tagihan', 'periode', 'status_bayar', 'tanggal_bayar')
                ->whereIn('pelanggan_id', $pelangganIds)
                ->where('periode', $period)
                ->get();
            foreach ($tagihans as $t) {
                $tagihansByPelanggan[(int) $t->pelanggan_id] = $t;
            }
        }

        $summary = [
            'total' => count($pelangganIds),
            'paid' => 0,
            'unpaid' => 0,
        ];
        foreach ($pelangganIds as $pid) {
            $t = $tagihansByPelanggan[(int) $pid] ?? null;
            $paid = false;
            if ($t) {
                $s = strtolower(trim((string) ($t->status_bayar ?? '')));
                $paid = in_array($s, ['sudah bayar', 'paid', 'lunas'], true);
            }
            if ($paid) $summary['paid']++; else $summary['unpaid']++;
        }

        return view('investor.index', compact('balance', 'rules', 'pelanggans', 'tagihansByPelanggan', 'summary', 'period', 'periodOptions', 'minStartPeriod'));
    }
}
