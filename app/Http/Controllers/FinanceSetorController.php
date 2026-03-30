<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceSetorController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:setor view|setor create|setor approve|setor export pdf']);
    }

    public function index(Request $request)
    {
        $this->ensureSetorEnabled();

        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $status = (string) $request->query('status', 'pending');
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');

        $query = DB::table('finance_setors as s')
            ->leftJoin('users as u', 's.depositor_id', '=', 'u.id')
            ->leftJoin('users as au', 's.approved_by', '=', 'au.id')
            ->leftJoin('bank_accounts as ba', 's.bank_account_id', '=', 'ba.id')
            ->leftJoin('banks as b', 'ba.bank_id', '=', 'b.id')
            ->select(
                's.*',
                'u.name as depositor_name',
                'u.email as depositor_email',
                'au.name as approved_by_name',
                'b.nama_bank as bank_name',
                'ba.pemilik_rekening as bank_owner',
                'ba.nomor_rekening as bank_number'
            )
            ->where('s.tenant_id', $tenantId)
            ->orderByDesc('s.deposited_at')
            ->orderByDesc('s.id');

        if (! auth()->user()?->can('setor approve')) {
            $query->where('s.depositor_id', (int) auth()->id());
        }
        if ($status !== '' && $status !== 'all') {
            $query->where('s.status', $status);
        }
        if ($from !== '') {
            $query->where('s.deposited_at', '>=', $from.' 00:00:00');
        }
        if ($to !== '') {
            $query->where('s.deposited_at', '<=', $to.' 23:59:59');
        }

        $rows = $query->paginate(20)->withQueryString();

        return view('finance-setors.index', compact('rows', 'status', 'from', 'to'));
    }

    public function create(Request $request)
    {
        $this->ensureSetorEnabled();

        abort_unless(auth()->user()?->can('setor create'), 403);

        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $areaCoverages = DB::table('area_coverages')
            ->orderBy('nama')
            ->get();

        $area = (string) $request->query('area', 'All');
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');

        $bankAccounts = DB::table('bank_accounts')
            ->join('banks', 'bank_accounts.bank_id', '=', 'banks.id')
            ->select('bank_accounts.*', 'banks.nama_bank')
            ->orderBy('banks.nama_bank')
            ->get();

        $query = DB::table('tagihans as t')
            ->join('pelanggans as p', 't.pelanggan_id', '=', 'p.id')
            ->leftJoin('area_coverages as a', 'p.coverage_area', '=', 'a.id')
            ->leftJoin('users as u', 't.created_by', '=', 'u.id')
            ->leftJoin('users as ru', 't.reviewed_by', '=', 'ru.id')
            ->where('t.tenant_id', $tenantId)
            ->where('p.tenant_id', $tenantId)
            ->where('t.status_bayar', 'Menunggu setor')
            ->whereNotNull('t.tanggal_review')
            ->whereNotNull('t.reviewed_by')
            ->whereIn('t.metode_bayar', ['Cash', 'Transfer Bank'])
            ->whereNotNull('t.tanggal_bayar')
            ->where('t.reviewed_by', (int) auth()->id())
            ->when(! empty($allowedAreas), fn ($q) => $q->whereIn('p.coverage_area', $allowedAreas))
            ->when($area !== '' && $area !== 'All', fn ($q) => $q->where('p.coverage_area', (int) $area))
            ->when($from !== '', fn ($q) => $q->where('t.tanggal_bayar', '>=', $from.' 00:00:00'))
            ->when($to !== '', fn ($q) => $q->where('t.tanggal_bayar', '<=', $to.' 23:59:59'))
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('pemasukans as pm')
                    ->whereRaw('pm.referense_id = t.id');
            })
            ->whereNotExists(function ($sub) use ($tenantId) {
                $sub->select(DB::raw(1))
                    ->from('finance_setor_items as si')
                    ->where('si.tenant_id', $tenantId)
                    ->whereRaw('si.tagihan_id = t.id');
            })
            ->select(
                't.id',
                't.no_tagihan',
                't.periode',
                't.total_bayar',
                't.metode_bayar',
                't.tanggal_bayar',
                'p.nama as pelanggan_nama',
                'p.no_layanan',
                'p.coverage_area as area_coverage_id',
                'a.nama as area_nama',
                'u.name as collector_name',
                'ru.name as validator_name'
            )
            ->orderBy('a.nama')
            ->orderBy('p.no_layanan')
            ->orderBy('t.periode');

        $items = $query->limit(500)->get();

        return view('finance-setors.create', compact('items', 'areaCoverages', 'area', 'from', 'to', 'bankAccounts'));
    }

    public function store(Request $request)
    {
        $this->ensureSetorEnabled();

        abort_unless(auth()->user()?->can('setor create'), 403);

        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $validated = $request->validate([
            'tagihan_ids' => 'required|array|min:1',
            'tagihan_ids.*' => 'integer|min:1',
            'deposited_at' => 'required|date',
            'method' => 'required|string|max:30',
            'bank_account_id' => 'nullable|integer|min:1',
            'note' => 'nullable|string|max:255',
            'embed' => 'nullable',
        ]);

        $tagihanIds = array_values(array_unique(array_map('intval', $validated['tagihan_ids'])));

        $allowedAreas = getAllowedAreaCoverageIdsForUser();

        $rows = DB::table('tagihans as t')
            ->join('pelanggans as p', 't.pelanggan_id', '=', 'p.id')
            ->where('t.tenant_id', $tenantId)
            ->where('p.tenant_id', $tenantId)
            ->whereIn('t.id', $tagihanIds)
            ->where('t.status_bayar', 'Menunggu setor')
            ->whereNotNull('t.tanggal_review')
            ->whereNotNull('t.reviewed_by')
            ->whereIn('t.metode_bayar', ['Cash', 'Transfer Bank'])
            ->whereNotNull('t.tanggal_bayar')
            ->where('t.reviewed_by', (int) auth()->id())
            ->when(! empty($allowedAreas), fn ($q) => $q->whereIn('p.coverage_area', $allowedAreas))
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('pemasukans as pm')
                    ->whereRaw('pm.referense_id = t.id');
            })
            ->whereNotExists(function ($sub) use ($tenantId) {
                $sub->select(DB::raw(1))
                    ->from('finance_setor_items as si')
                    ->where('si.tenant_id', $tenantId)
                    ->whereRaw('si.tagihan_id = t.id');
            })
            ->select('t.id', 't.pelanggan_id', 't.periode', 't.total_bayar', 'p.coverage_area as area_coverage_id')
            ->get();

        if ($rows->isEmpty()) {
            return back()->with('error', 'Tidak ada tagihan yang valid untuk disetor.')->withInput();
        }

        $depositedAt = Carbon::parse((string) $validated['deposited_at']);
        $method = trim((string) $validated['method']);
        $bankAccountId = ! empty($validated['bank_account_id']) ? (int) $validated['bank_account_id'] : null;
        $note = isset($validated['note']) ? trim((string) $validated['note']) : null;
        $note = $note !== null && $note !== '' ? $note : null;

        $totalNominal = (int) $rows->sum('total_bayar');
        $totalItems = (int) $rows->count();

        $code = 'ST'.now()->format('YmdHis').strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

        DB::beginTransaction();
        try {
            $setorId = DB::table('finance_setors')->insertGetId([
                'tenant_id' => $tenantId,
                'code' => $code,
                'depositor_id' => (int) auth()->id(),
                'deposited_at' => $depositedAt->format('Y-m-d H:i:s'),
                'method' => $method,
                'bank_account_id' => $bankAccountId,
                'status' => 'pending',
                'note' => $note,
                'total_nominal' => $totalNominal,
                'total_items' => $totalItems,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $now = now();
            foreach ($rows as $r) {
                DB::table('finance_setor_items')->insert([
                    'tenant_id' => $tenantId,
                    'setor_id' => (int) $setorId,
                    'tagihan_id' => (int) $r->id,
                    'pelanggan_id' => ! empty($r->pelanggan_id) ? (int) $r->pelanggan_id : null,
                    'area_coverage_id' => ! empty($r->area_coverage_id) ? (int) $r->area_coverage_id : null,
                    'periode' => ! empty($r->periode) ? (string) $r->periode : null,
                    'nominal' => (int) ($r->total_bayar ?? 0),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::commit();
            $params = $request->boolean('embed') ? ['embed' => 1] : [];

            return redirect()->route('finance-setors.index', $params)->with('success', 'Setor berhasil dibuat dan menunggu approval.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal membuat setor: '.$e->getMessage())->withInput();
        }
    }

    public function approve(Request $request, int $id)
    {
        $this->ensureSetorEnabled();

        abort_unless(auth()->user()?->can('setor approve'), 403);

        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_unless(DB::getSchemaBuilder()->hasTable('finance_setors'), 500);
        abort_unless(DB::getSchemaBuilder()->hasTable('finance_setor_items'), 500);
        abort_unless(DB::getSchemaBuilder()->hasTable('pemasukans'), 500);

        DB::beginTransaction();
        try {
            $setor = DB::table('finance_setors')->where('tenant_id', $tenantId)->where('id', $id)->lockForUpdate()->first();
            if (! $setor) {
                DB::rollBack();

                return back()->with('error', 'Data setor tidak ditemukan.');
            }
            if ((string) ($setor->status ?? '') !== 'pending') {
                DB::rollBack();

                return back()->with('error', 'Setor sudah diproses.');
            }

            $items = DB::table('finance_setor_items as si')
                ->join('tagihans as t', 'si.tagihan_id', '=', 't.id')
                ->join('pelanggans as p', 't.pelanggan_id', '=', 'p.id')
                ->select('si.*', 't.no_tagihan', 't.periode', 't.metode_bayar', 't.total_bayar', 't.pelanggan_id', 'p.nama as pelanggan_nama')
                ->where('si.tenant_id', $tenantId)
                ->where('si.setor_id', $id)
                ->get();

            $now = now();
            foreach ($items as $it) {
                $exists = DB::table('pemasukans')->where('referense_id', (int) $it->tagihan_id)->exists();
                if ($exists) {
                    continue;
                }
                $categoryId = getInternetIncomeCategoryIdForPelanggan((int) ($it->pelanggan_id ?? 0));
                DB::table('pemasukans')->insert([
                    'tenant_id' => $tenantId,
                    'nominal' => (int) ($it->total_bayar ?? $it->nominal ?? 0),
                    'tanggal' => (string) ($setor->deposited_at ?? $now),
                    'category_pemasukan_id' => $categoryId,
                    'keterangan' => 'Setor '.($setor->code ?? '-').' - Pembayaran Tagihan '.($it->no_tagihan ?? '-').' a/n '.(string) ($it->pelanggan_nama ?? ($it->pelanggan_id ?? '-')).' Periode '.(string) ($it->periode ?? '-'),
                    'referense_id' => (int) $it->tagihan_id,
                    'metode_bayar' => (string) ($it->metode_bayar ?? ($setor->method ?? 'Cash')),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                applyInvestorSharingForPaidTagihan((int) $it->tagihan_id);

                DB::table('tagihans')->where('tenant_id', $tenantId)->where('id', (int) $it->tagihan_id)->update([
                    'status_bayar' => 'Sudah Bayar',
                    'updated_at' => $now,
                ]);
            }

            DB::table('finance_setors')->where('tenant_id', $tenantId)->where('id', $id)->update([
                'status' => 'approved',
                'approved_by' => (int) auth()->id(),
                'approved_at' => $now,
                'updated_at' => $now,
            ]);

            DB::commit();

            return back()->with('success', 'Setor berhasil di-approve dan diposting ke pemasukan.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal approve setor: '.$e->getMessage());
        }
    }

    public function reject(Request $request, int $id)
    {
        $this->ensureSetorEnabled();

        abort_unless(auth()->user()?->can('setor approve'), 403);

        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        DB::beginTransaction();
        try {
            $setor = DB::table('finance_setors')->where('tenant_id', $tenantId)->where('id', $id)->lockForUpdate()->first();
            if (! $setor) {
                DB::rollBack();

                return back()->with('error', 'Data setor tidak ditemukan.');
            }
            if ((string) ($setor->status ?? '') !== 'pending') {
                DB::rollBack();

                return back()->with('error', 'Setor sudah diproses.');
            }

            $validated = $request->validate([
                'note' => 'nullable|string|max:255',
            ]);
            $note = isset($validated['note']) ? trim((string) $validated['note']) : null;
            $note = $note !== null && $note !== '' ? $note : null;

            DB::table('finance_setors')->where('tenant_id', $tenantId)->where('id', $id)->update([
                'status' => 'rejected',
                'approved_by' => (int) auth()->id(),
                'approved_at' => now(),
                'note' => $note,
                'updated_at' => now(),
            ]);
            DB::commit();

            return back()->with('success', 'Setor ditolak.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menolak setor: '.$e->getMessage());
        }
    }

    public function exportPdf(Request $request, int $id)
    {
        $this->ensureSetorEnabled();

        abort_unless(auth()->user()?->can('setor export pdf'), 403);

        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $setor = DB::table('finance_setors as s')
            ->leftJoin('users as u', 's.depositor_id', '=', 'u.id')
            ->leftJoin('bank_accounts as ba', 's.bank_account_id', '=', 'ba.id')
            ->leftJoin('banks as b', 'ba.bank_id', '=', 'b.id')
            ->select('s.*', 'u.name as depositor_name', 'b.nama_bank as bank_name', 'ba.nomor_rekening as bank_number', 'ba.pemilik_rekening as bank_owner')
            ->where('s.tenant_id', $tenantId)
            ->where('s.id', $id)
            ->first();
        abort_if(! $setor, 404);

        $items = DB::table('finance_setor_items as si')
            ->join('tagihans as t', 'si.tagihan_id', '=', 't.id')
            ->join('pelanggans as p', 't.pelanggan_id', '=', 'p.id')
            ->leftJoin('area_coverages as a', 'p.coverage_area', '=', 'a.id')
            ->select(
                'si.nominal',
                't.periode',
                't.no_tagihan',
                'p.nama as pelanggan_nama',
                'p.no_layanan',
                'a.nama as area_nama'
            )
            ->where('si.tenant_id', $tenantId)
            ->where('si.setor_id', $id)
            ->orderBy('a.nama')
            ->orderBy('p.no_layanan')
            ->orderBy('t.periode')
            ->get();

        $grouped = [];
        foreach ($items as $it) {
            $areaName = (string) ($it->area_nama ?? 'Tanpa Area');
            if (! isset($grouped[$areaName])) {
                $grouped[$areaName] = [
                    'items' => [],
                    'subtotal' => 0,
                ];
            }
            $grouped[$areaName]['items'][] = $it;
            $grouped[$areaName]['subtotal'] += (int) ($it->nominal ?? 0);
        }

        $pdf = Pdf::loadView('finance-setors.export-pdf', [
            'title' => 'Laporan Setor',
            'setor' => $setor,
            'grouped' => $grouped,
            'totalNominal' => (int) ($setor->total_nominal ?? 0),
            'totalItems' => (int) ($setor->total_items ?? 0),
            'tenantId' => $tenantId,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('setor_'.($setor->code ?? $id).'.pdf');
    }

    private function setorEnabledFrom(): Carbon
    {
        $settingWeb = getSettingWeb();
        $val = $settingWeb?->setor_enabled_from ?? null;
        try {
            return $val ? Carbon::parse((string) $val) : Carbon::parse('2026-04-01 00:00:00');
        } catch (\Throwable $e) {
            return Carbon::parse('2026-04-01 00:00:00');
        }
    }

    private function ensureSetorEnabled(): void
    {
        $enabledFrom = $this->setorEnabledFrom();
        if (now()->lessThan($enabledFrom)) {
            abort(403, 'Fitur setor aktif mulai '.$enabledFrom->format('d-m-Y').'.');
        }
    }
}
