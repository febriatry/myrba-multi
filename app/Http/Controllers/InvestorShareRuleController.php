<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvestorShareRuleController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'web', 'permission:investor rule manage']);
    }

    public function index()
    {
        $rules = DB::table('investor_share_rules')
            ->leftJoin('users', 'investor_share_rules.user_id', '=', 'users.id')
            ->leftJoin('area_coverages', 'investor_share_rules.coverage_area_id', '=', 'area_coverages.id')
            ->leftJoin('packages', 'investor_share_rules.package_id', '=', 'packages.id')
            ->leftJoin('investor_share_rule_pelanggans as irp', function ($join) {
                $join->on('investor_share_rules.id', '=', 'irp.rule_id')
                    ->where('irp.is_included', '=', 'Yes');
            })
            ->select(
                'investor_share_rules.*',
                'users.name as user_name',
                'area_coverages.nama as area_nama',
                'packages.nama_layanan as paket_nama',
                DB::raw('COUNT(irp.id) as pelanggan_selected_count')
            )
            ->groupBy(
                'investor_share_rules.id',
                'investor_share_rules.user_id',
                'investor_share_rules.rule_type',
                'investor_share_rules.coverage_area_id',
                'investor_share_rules.package_id',
                'investor_share_rules.start_period',
                'investor_share_rules.amount_type',
                'investor_share_rules.amount_value',
                'investor_share_rules.is_aktif',
                'investor_share_rules.created_at',
                'investor_share_rules.updated_at',
                'users.name',
                'area_coverages.nama',
                'packages.nama_layanan'
            )
            ->orderByDesc('investor_share_rules.id')
            ->get();
        return view('investor-share-rules.index', compact('rules'));
    }

    public function create()
    {
        $users = DB::table('users')->select('id', 'name', 'email')->orderBy('name')->get();
        $areas = DB::table('area_coverages')->select('id', 'nama')->orderBy('nama')->get();
        $packages = DB::table('packages')->select('id', 'nama_layanan')->orderBy('nama_layanan')->get();
        return view('investor-share-rules.create', compact('users', 'areas', 'packages'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'rule_type' => 'required|in:per_customer,per_area,per_package',
            'coverage_area_id' => 'nullable|integer',
            'package_id' => 'nullable|integer',
            'start_period' => 'nullable|string|max:7',
            'amount_type' => 'required|in:fixed,percent',
            'amount_value' => 'required|numeric|min:0',
            'is_aktif' => 'required|in:Yes,No',
        ]);

        DB::table('investor_share_rules')->insert([
            'user_id' => (int) $validated['user_id'],
            'rule_type' => (string) $validated['rule_type'],
            'coverage_area_id' => !empty($validated['coverage_area_id']) ? (int) $validated['coverage_area_id'] : null,
            'package_id' => !empty($validated['package_id']) ? (int) $validated['package_id'] : null,
            'start_period' => !empty($validated['start_period']) ? trim((string) $validated['start_period']) : null,
            'amount_type' => (string) $validated['amount_type'],
            'amount_value' => (float) $validated['amount_value'],
            'is_aktif' => (string) $validated['is_aktif'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('investor-share-rules.index')->with('success', 'Rule bagi hasil berhasil dibuat.');
    }

    public function edit($id)
    {
        $rule = DB::table('investor_share_rules')->where('id', (int) $id)->first();
        abort_if(!$rule, 404);
        $users = DB::table('users')->select('id', 'name', 'email')->orderBy('name')->get();
        $areas = DB::table('area_coverages')->select('id', 'nama')->orderBy('nama')->get();
        $packages = DB::table('packages')->select('id', 'nama_layanan')->orderBy('nama_layanan')->get();
        return view('investor-share-rules.edit', compact('rule', 'users', 'areas', 'packages'));
    }

    public function update(Request $request, $id)
    {
        $rule = DB::table('investor_share_rules')->where('id', (int) $id)->first();
        abort_if(!$rule, 404);
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'rule_type' => 'required|in:per_customer,per_area,per_package',
            'coverage_area_id' => 'nullable|integer',
            'package_id' => 'nullable|integer',
            'start_period' => 'nullable|string|max:7',
            'amount_type' => 'required|in:fixed,percent',
            'amount_value' => 'required|numeric|min:0',
            'is_aktif' => 'required|in:Yes,No',
        ]);

        DB::table('investor_share_rules')->where('id', (int) $id)->update([
            'user_id' => (int) $validated['user_id'],
            'rule_type' => (string) $validated['rule_type'],
            'coverage_area_id' => !empty($validated['coverage_area_id']) ? (int) $validated['coverage_area_id'] : null,
            'package_id' => !empty($validated['package_id']) ? (int) $validated['package_id'] : null,
            'start_period' => !empty($validated['start_period']) ? trim((string) $validated['start_period']) : null,
            'amount_type' => (string) $validated['amount_type'],
            'amount_value' => (float) $validated['amount_value'],
            'is_aktif' => (string) $validated['is_aktif'],
            'updated_at' => now(),
        ]);

        return redirect()->route('investor-share-rules.index')->with('success', 'Rule bagi hasil berhasil diperbarui.');
    }

    public function destroy($id)
    {
        DB::table('investor_share_rules')->where('id', (int) $id)->delete();
        return redirect()->route('investor-share-rules.index')->with('success', 'Rule bagi hasil berhasil dihapus.');
    }

    public function customers(Request $request, $id)
    {
        $rule = DB::table('investor_share_rules')
            ->leftJoin('users', 'investor_share_rules.user_id', '=', 'users.id')
            ->select('investor_share_rules.*', 'users.name as user_name')
            ->where('investor_share_rules.id', (int) $id)
            ->first();
        abort_if(!$rule, 404);

        $selectedIds = DB::table('investor_share_rule_pelanggans')
            ->where('rule_id', (int) $id)
            ->where('is_included', 'Yes')
            ->pluck('pelanggan_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $areas = DB::table('area_coverages')->select('id', 'nama')->orderBy('nama')->get();
        $packages = DB::table('packages')->select('id', 'nama_layanan')->orderBy('nama_layanan')->get();

        $q = DB::table('pelanggans')
            ->leftJoin('area_coverages', 'pelanggans.coverage_area', '=', 'area_coverages.id')
            ->leftJoin('packages', 'pelanggans.paket_layanan', '=', 'packages.id')
            ->select(
                'pelanggans.id',
                'pelanggans.nama',
                'pelanggans.no_layanan',
                'area_coverages.nama as area_nama',
                'packages.nama_layanan as paket_nama'
            )
            ->where('pelanggans.status_berlangganan', 'Aktif');

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $q->where(function ($qq) use ($search) {
                $qq->where('pelanggans.no_layanan', 'like', '%' . $search . '%')
                    ->orWhere('pelanggans.nama', 'like', '%' . $search . '%');
            });
        }

        $areaId = $request->query('area_id');
        $packageId = $request->query('package_id');

        if ($areaId !== null && $areaId !== '') {
            $q->where('pelanggans.coverage_area', (int) $areaId);
        } elseif ($rule->rule_type === 'per_area' && !empty($rule->coverage_area_id)) {
            $q->where('pelanggans.coverage_area', (int) $rule->coverage_area_id);
        }

        if ($packageId !== null && $packageId !== '') {
            $q->where('pelanggans.paket_layanan', (int) $packageId);
        } elseif ($rule->rule_type === 'per_package' && !empty($rule->package_id)) {
            $q->where('pelanggans.paket_layanan', (int) $rule->package_id);
        }

        $pelanggans = $q->orderBy('pelanggans.no_layanan')->paginate(30)->withQueryString();

        return view('investor-share-rules.customers', compact('rule', 'pelanggans', 'selectedIds', 'areas', 'packages'));
    }

    public function customersUpdate(Request $request, $id)
    {
        $rule = DB::table('investor_share_rules')->where('id', (int) $id)->first();
        abort_if(!$rule, 404);

        $ids = $request->input('pelanggan_ids', []);
        $ids = is_array($ids) ? $ids : [];
        $ids = array_values(array_unique(array_map('intval', $ids)));

        $pageIds = $request->input('page_pelanggan_ids', []);
        $pageIds = is_array($pageIds) ? $pageIds : [];
        $pageIds = array_values(array_unique(array_map('intval', $pageIds)));

        DB::transaction(function () use ($id, $ids, $pageIds) {
            $base = DB::table('investor_share_rule_pelanggans')->where('rule_id', (int) $id);
            if (!empty($pageIds)) {
                $base->whereIn('pelanggan_id', $pageIds)->delete();
            } else {
                $base->delete();
            }
            if (!empty($ids)) {
                $rows = [];
                foreach ($ids as $pid) {
                    $rows[] = [
                        'rule_id' => (int) $id,
                        'pelanggan_id' => (int) $pid,
                        'is_included' => 'Yes',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DB::table('investor_share_rule_pelanggans')->insertOrIgnore($rows);
            }
        });

        return redirect()->route('investor-share-rules.customers', (int) $id)->with('success', 'Checklist pelanggan berhasil disimpan.');
    }

    public function backfill($id)
    {
        $rule = DB::table('investor_share_rules')
            ->leftJoin('users', 'investor_share_rules.user_id', '=', 'users.id')
            ->leftJoin('area_coverages', 'investor_share_rules.coverage_area_id', '=', 'area_coverages.id')
            ->leftJoin('packages', 'investor_share_rules.package_id', '=', 'packages.id')
            ->select(
                'investor_share_rules.*',
                'users.name as user_name',
                'area_coverages.nama as area_nama',
                'packages.nama_layanan as paket_nama'
            )
            ->where('investor_share_rules.id', (int) $id)
            ->first();
        abort_if(!$rule, 404);

        $startPeriod = trim((string) ($rule->start_period ?? ''));
        $periodOptions = [];
        try {
            $end = Carbon::now()->startOfMonth();
            $start = $startPeriod !== '' ? Carbon::createFromFormat('Y-m', $startPeriod)->startOfMonth() : $end->copy()->subMonths(23);
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

        $defaults = [
            'from_period' => !empty($periodOptions) ? end($periodOptions) : Carbon::now()->format('Y-m'),
            'to_period' => !empty($periodOptions) ? $periodOptions[0] : Carbon::now()->format('Y-m'),
        ];

        return view('investor-share-rules.backfill', compact('rule', 'periodOptions', 'defaults', 'startPeriod'));
    }

    public function backfillRun(Request $request, $id)
    {
        $rule = DB::table('investor_share_rules')->where('id', (int) $id)->first();
        abort_if(!$rule, 404);

        $validated = $request->validate([
            'from_period' => 'required|string|max:7',
            'to_period' => 'required|string|max:7',
            'dry_run' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:20000',
            'mode' => 'nullable|string',
        ]);

        $from = trim((string) $validated['from_period']);
        $to = trim((string) $validated['to_period']);
        if (strcmp($from, $to) > 0) {
            [$from, $to] = [$to, $from];
        }

        $startPeriod = trim((string) ($rule->start_period ?? ''));
        if ($startPeriod !== '' && strcmp($from, $startPeriod) < 0) {
            $from = $startPeriod;
        }
        if ($startPeriod !== '' && strcmp($to, $startPeriod) < 0) {
            $to = $startPeriod;
        }

        $mode = strtolower(trim((string) ($validated['mode'] ?? 'backfill')));
        if (!in_array($mode, ['backfill', 'recalculate'], true)) {
            $mode = 'backfill';
        }

        $paidStatuses = ['sudah bayar', 'paid', 'lunas'];
        $basePaid = DB::table('tagihans')
            ->select('tagihans.id')
            ->whereRaw("LOWER(TRIM(tagihans.status_bayar)) IN ('" . implode("','", $paidStatuses) . "')")
            ->where('tagihans.periode', '>=', $from)
            ->where('tagihans.periode', '<=', $to);

        $q = DB::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->leftJoin('packages', 'pelanggans.paket_layanan', '=', 'packages.id')
            ->select(
                'tagihans.id as id',
                'tagihans.no_tagihan',
                'tagihans.pelanggan_id',
                'tagihans.periode',
                'tagihans.status_bayar',
                'tagihans.total_bayar',
                'pelanggans.no_layanan',
                'pelanggans.nama',
                'pelanggans.coverage_area',
                'pelanggans.paket_layanan',
                'packages.harga as harga_paket'
            )
            ->whereRaw("LOWER(TRIM(tagihans.status_bayar)) IN ('" . implode("','", $paidStatuses) . "')")
            ->where('tagihans.periode', '>=', $from)
            ->where('tagihans.periode', '<=', $to);

        $manualList = DB::getSchemaBuilder()->hasTable('investor_share_rule_pelanggans')
            ? DB::table('investor_share_rule_pelanggans')
                ->where('rule_id', (int) $id)
                ->where('is_included', 'Yes')
                ->pluck('pelanggan_id')
                ->map(fn ($v) => (int) $v)
                ->all()
            : [];

        if (!empty($manualList)) {
            $q->whereIn('tagihans.pelanggan_id', $manualList);
        } else {
            if ($rule->rule_type === 'per_area' && !empty($rule->coverage_area_id)) {
                $q->where('pelanggans.coverage_area', (int) $rule->coverage_area_id);
            }
            if ($rule->rule_type === 'per_package' && !empty($rule->package_id)) {
                $q->where('pelanggans.paket_layanan', (int) $rule->package_id);
            }
        }

        $limit = $request->input('limit');
        if (!empty($limit)) {
            $q->limit((int) $limit);
        }

        $totalPaid = (clone $basePaid)->count();
        $totalMatched = (clone $q)->count();
        $existingEarnings = DB::table('investor_earnings')
            ->where('rule_id', (int) $id)
            ->where('periode', '>=', $from)
            ->where('periode', '<=', $to)
            ->count();
        $estimatedNew = max(0, $totalMatched - $existingEarnings);

        $samples = (clone $q)->orderBy('tagihans.id')
            ->limit(10)
            ->get();

        $dryRun = (string) ($validated['dry_run'] ?? '') === '1';
        if ($dryRun) {
            return redirect()
                ->route('investor-share-rules.backfill', (int) $id)
                ->with('backfill_report', [
                    'from' => $from,
                    'to' => $to,
                    'mode' => $mode,
                    'total_paid' => $totalPaid,
                    'total_matched' => $totalMatched,
                    'existing_earnings' => $existingEarnings,
                    'estimated_new' => $estimatedNew,
                    'samples' => $samples,
                ])
                ->with('success', ($mode === 'recalculate' ? 'Dry-run Recalculate. ' : 'Dry-run Backfill. ') . 'Total paid: ' . number_format($totalPaid) . ', match rule: ' . number_format($totalMatched) . ', estimasi kredit baru: ' . number_format($estimatedNew) . '.');
        }

        $processed = 0;
        $credited = 0;
        $skipped = 0;
        $reversed = 0;
        $deltaAmount = 0.0;

        $walletHistoryHasRef = DB::getSchemaBuilder()->hasColumn('investor_wallet_histories', 'rule_id') && DB::getSchemaBuilder()->hasColumn('investor_wallet_histories', 'tagihan_id');
        $earningHasRecalc = DB::getSchemaBuilder()->hasColumn('investor_earnings', 'is_reversed');

        $walletUserId = (int) ($rule->user_id ?? 0);
        $ruleStartPeriod = trim((string) ($rule->start_period ?? ''));

        if ($mode === 'backfill') {
            $q->orderBy('tagihans.id')->chunkById(200, function ($rows) use (&$processed, &$credited, &$skipped, $id, $walletUserId, $walletHistoryHasRef, $ruleStartPeriod) {
                foreach ($rows as $row) {
                    $processed++;
                    $exists = DB::table('investor_earnings')
                        ->where('rule_id', (int) $id)
                        ->where('tagihan_id', (int) $row->id)
                        ->exists();
                    if ($exists) {
                        $skipped++;
                        continue;
                    }
                    $ok = applyInvestorSharingForPaidTagihanForRule((int) $row->id, (int) $id);
                    if ($ok) {
                        $credited++;
                    } else {
                        $skipped++;
                    }
                }
            }, 'tagihans.id', 'id');
        } else {
            $existingRows = DB::table('investor_earnings')
                ->select('id', 'tagihan_id', 'amount', DB::raw("COALESCE(is_reversed,'No') as is_reversed"))
                ->where('rule_id', (int) $id)
                ->where('periode', '>=', $from)
                ->where('periode', '<=', $to)
                ->get();
            $existingMap = [];
            foreach ($existingRows as $er) {
                $tid = (int) ($er->tagihan_id ?? 0);
                if ($tid > 0) {
                    $existingMap[$tid] = $er;
                }
            }
            $expectedTagihanIds = [];

            $q->orderBy('tagihans.id')->chunkById(200, function ($rows) use (&$processed, &$credited, &$skipped, &$reversed, &$deltaAmount, $id, $walletUserId, $walletHistoryHasRef, $earningHasRecalc, &$existingMap, &$expectedTagihanIds, $rule) {
                DB::transaction(function () use ($rows, &$processed, &$credited, &$skipped, &$deltaAmount, $id, $walletUserId, $walletHistoryHasRef, $earningHasRecalc, &$existingMap, &$expectedTagihanIds, $rule) {
                    $wallet = DB::table('investor_wallets')->where('user_id', $walletUserId)->lockForUpdate()->first();
                    if (!$wallet) {
                        DB::table('investor_wallets')->insert([
                            'user_id' => $walletUserId,
                            'balance' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $wallet = DB::table('investor_wallets')->where('user_id', $walletUserId)->lockForUpdate()->first();
                    }
                    $balance = (float) ($wallet->balance ?? 0);

                    foreach ($rows as $row) {
                        $processed++;
                        $tagihanId = (int) $row->id;
                        $expectedTagihanIds[$tagihanId] = true;

                        $baseTotal = (float) ($row->total_bayar ?? 0);
                        $baseHargaPaket = (float) ($row->harga_paket ?? 0);
                        $amount = 0.0;
                        if ((string) $rule->amount_type === 'percent') {
                            $base = $baseTotal > 0 ? $baseTotal : ($baseHargaPaket > 0 ? $baseHargaPaket : 0);
                            $amount = round($base * ((float) $rule->amount_value) / 100.0, 2);
                        } else {
                            $amount = (float) $rule->amount_value;
                        }
                        if ($amount <= 0) {
                            $skipped++;
                            continue;
                        }

                        $existing = $existingMap[$tagihanId] ?? null;
                        if ($existing && strtolower(trim((string) $existing->is_reversed)) !== 'yes') {
                            $skipped++;
                            continue;
                        }

                        if ($existing) {
                            $update = ['amount' => $amount];
                            if ($earningHasRecalc) {
                                $update['is_reversed'] = 'No';
                                $update['reversed_at'] = null;
                                $update['reversed_by'] = null;
                            }
                            DB::table('investor_earnings')->where('id', (int) $existing->id)->update($update);
                        } else {
                            $insert = [
                                'user_id' => $walletUserId,
                                'rule_id' => (int) $id,
                                'pelanggan_id' => (int) ($row->pelanggan_id ?? 0) ?: null,
                                'tagihan_id' => $tagihanId,
                                'periode' => !empty($row->periode) ? (string) $row->periode : null,
                                'amount' => $amount,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                            if ($earningHasRecalc) {
                                $insert['is_reversed'] = 'No';
                            }
                            DB::table('investor_earnings')->insertOrIgnore($insert);
                        }

                        $before = $balance;
                        $balance = $balance + $amount;
                        DB::table('investor_wallets')->where('user_id', $walletUserId)->update([
                            'balance' => $balance,
                            'updated_at' => now(),
                        ]);
                        $hist = [
                            'user_id' => $walletUserId,
                            'type' => 'Credit',
                            'amount' => $amount,
                            'balance_before' => $before,
                            'balance_after' => $balance,
                            'description' => 'Recalculate kredit tagihan ' . ($row->no_tagihan ?? '-') . ' pelanggan ' . ($row->nama ?? '-') . ' (' . ($row->no_layanan ?? '-') . ')',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        if ($walletHistoryHasRef) {
                            $hist['rule_id'] = (int) $id;
                            $hist['tagihan_id'] = $tagihanId;
                        }
                        DB::table('investor_wallet_histories')->insert($hist);
                        $credited++;
                        $deltaAmount += $amount;
                    }
                });
            }, 'tagihans.id', 'id');

            $toReverse = [];
            foreach ($existingMap as $tagihanId => $er) {
                if (!isset($expectedTagihanIds[$tagihanId]) && strtolower(trim((string) ($er->is_reversed ?? 'No'))) !== 'yes') {
                    $toReverse[] = $er;
                }
            }

            $chunks = array_chunk($toReverse, 200);
            foreach ($chunks as $chunk) {
                DB::transaction(function () use ($chunk, &$reversed, &$deltaAmount, $walletUserId, $walletHistoryHasRef, $earningHasRecalc, $id) {
                    $wallet = DB::table('investor_wallets')->where('user_id', $walletUserId)->lockForUpdate()->first();
                    if (!$wallet) {
                        DB::table('investor_wallets')->insert([
                            'user_id' => $walletUserId,
                            'balance' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $wallet = DB::table('investor_wallets')->where('user_id', $walletUserId)->lockForUpdate()->first();
                    }
                    $balance = (float) ($wallet->balance ?? 0);
                    foreach ($chunk as $er) {
                        $amount = (float) ($er->amount ?? 0);
                        if ($amount <= 0) {
                            continue;
                        }
                        $before = $balance;
                        $balance = $balance - $amount;
                        DB::table('investor_wallets')->where('user_id', $walletUserId)->update([
                            'balance' => $balance,
                            'updated_at' => now(),
                        ]);
                        $hist = [
                            'user_id' => $walletUserId,
                            'type' => 'Debit',
                            'amount' => $amount,
                            'balance_before' => $before,
                            'balance_after' => $balance,
                            'description' => 'Recalculate reversal tagihan_id ' . (string) ($er->tagihan_id ?? '-'),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        if ($walletHistoryHasRef) {
                            $hist['rule_id'] = (int) $id;
                            $hist['tagihan_id'] = (int) ($er->tagihan_id ?? 0) ?: null;
                        }
                        DB::table('investor_wallet_histories')->insert($hist);

                        if ($earningHasRecalc) {
                            DB::table('investor_earnings')->where('id', (int) $er->id)->update([
                                'is_reversed' => 'Yes',
                                'reversed_at' => now(),
                                'reversed_by' => auth()->id(),
                                'updated_at' => now(),
                            ]);
                        }
                        $reversed++;
                        $deltaAmount -= $amount;
                    }
                });
            }
        }

        return redirect()
            ->route('investor-share-rules.backfill', (int) $id)
            ->with('backfill_report', [
                'from' => $from,
                'to' => $to,
                'mode' => $mode,
                'total_paid' => $totalPaid,
                'total_matched' => $totalMatched,
                'existing_earnings' => $existingEarnings,
                'estimated_new' => $estimatedNew,
                'processed' => $processed,
                'credited' => $credited,
                'skipped' => $skipped,
                'reversed' => $reversed,
                'delta_amount' => $deltaAmount,
                'samples' => $samples,
            ])
            ->with('success', ($mode === 'recalculate' ? 'Recalculate selesai. ' : 'Backfill selesai. ') . 'Periode ' . $from . ' s/d ' . $to . '. Diproses: ' . number_format($processed) . ', kredit baru: ' . number_format($credited) . ', reversal: ' . number_format($reversed) . ', skip: ' . number_format($skipped) . '.');
    }
}
