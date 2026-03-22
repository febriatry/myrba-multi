<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Services\InventoryStockService;
use App\Models\Settingmikrotik;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class TransaksiStockOutController extends Controller
{
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            $transaksis = Transaksi::where('jenis_transaksi', 'out')
                ->with('user');

            return DataTables::of($transaksis)
                ->addIndexColumn()
                ->addColumn('user_name', fn($transaksi) => $transaksi->user->name)
                ->addColumn('action', fn($transaksi) => view('transaksi-stock-out.include.action', compact('transaksi')))
                ->toJson();
        }
        return view('transaksi-stock-out.index');
    }

    public function create(): View
    {
        $barangs = Barang::orderBy('nama_barang')->get();
        $investorOwners = $this->investorOwners();
        return view('transaksi-stock-out.create', compact('barangs', 'investorOwners'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'tanggal_transaksi' => 'required|date',
            'kode_transaksi' => 'required|string|unique:transaksi,kode_transaksi',
            'cart_items_json' => 'required|json'
        ]);

        DB::beginTransaction();
        try {

            $cartItems = json_decode($request->cart_items_json, true);
            $purposes = [];
            $targets = [];
            foreach ($cartItems as $item) {
                $purposes[] = strtolower(trim((string) ($item['purpose'] ?? 'umum')));
                $targets[] = (int) ($item['target_pelanggan_id'] ?? 0);
            }
            $uniquePurposes = array_values(array_unique(array_filter($purposes)));
            if (count($uniquePurposes) !== 1) {
                DB::rollBack();
                return back()->with('error', 'Kategori tujuan transaksi harus satu jenis.');
            }
            $purpose = $uniquePurposes[0] ?? 'umum';
            $targetPelangganId = null;
            if ($purpose === 'repair_pelanggan') {
                $uniqueTargets = array_values(array_unique(array_filter($targets)));
                if (count($uniqueTargets) !== 1) {
                    DB::rollBack();
                    return back()->with('error', 'Repair pelanggan wajib memilih satu pelanggan yang sama untuk semua item.');
                }
                $targetPelangganId = (int) $uniqueTargets[0];
            }

            foreach ($cartItems as $item) {
                [$ownerType, $ownerUserId] = $this->normalizeOwner($item['owner_type'] ?? 'office', $item['owner_user_id'] ?? null);
                if ($ownerType === 'investor' && in_array($purpose, ['jual', 'repair_umum', 'repair_pelanggan'], true)) {
                    DB::rollBack();
                    return back()->with('error', 'Barang milik investor tidak boleh untuk jual atau repair.');
                }
                if ($ownerType === 'office' && $purpose === 'jual') {
                }
                if ($ownerType === 'office' && in_array($purpose, ['repair_umum', 'repair_pelanggan'], true)) {
                }
                if ($ownerType !== 'office' && $purpose === 'jual') {
                    DB::rollBack();
                    return back()->with('error', 'Kategori tujuan jual hanya untuk barang milik kantor.');
                }
                if ($ownerType !== 'office' && in_array($purpose, ['repair_umum', 'repair_pelanggan'], true)) {
                    DB::rollBack();
                    return back()->with('error', 'Kategori tujuan repair hanya untuk barang milik kantor.');
                }
                $stockQty = InventoryStockService::getOwnerQty((int) $item['id'], $ownerType, $ownerUserId);
                if ($stockQty < (int) $item['qty']) {
                    $barang = Barang::find($item['id']);
                    $ownerLabel = InventoryStockService::ownerLabel($ownerType, $this->ownerName($ownerUserId));
                    DB::rollBack();
                    return back()->with('error', "Stok '{$barang->nama_barang}' milik {$ownerLabel} tidak mencukupi.");
                }
            }

            $transaksi = Transaksi::create([
                'user_id' => Auth::id(),
                'kode_transaksi' => $request->kode_transaksi,
                'tanggal_transaksi' => $request->tanggal_transaksi,
                'jenis_transaksi' => 'out',
                'keterangan' => $request->keterangan,
            ]);

            $totalSale = 0;
            foreach ($cartItems as $item) {
                [$ownerType, $ownerUserId] = $this->normalizeOwner($item['owner_type'] ?? 'office', $item['owner_user_id'] ?? null);
                $pricing = InventoryStockService::getOwnerPricing((int) $item['id'], $ownerType, $ownerUserId);
                $hppUnit = (int) ($pricing['hpp_unit'] ?? 0);
                $hargaJualUnit = (int) ($pricing['harga_jual_unit'] ?? 0);
                TransaksiDetail::create([
                    'transaksi_id' => $transaksi->id,
                    'barang_id' => $item['id'],
                    'owner_type' => $ownerType,
                    'owner_user_id' => $ownerUserId,
                    'jumlah' => $item['qty'],
                    'hpp_unit' => $hppUnit,
                    'harga_jual_unit' => $hargaJualUnit,
                    'purpose' => $purpose,
                    'purpose_scope' => $purpose === 'repair_pelanggan' ? 'pelanggan' : ($purpose === 'repair_umum' ? 'umum' : null),
                    'target_pelanggan_id' => $targetPelangganId,
                ]);
                $ok = InventoryStockService::decrease((int) $item['id'], $ownerType, $ownerUserId, (int) $item['qty']);
                if (!$ok) {
                    DB::rollBack();
                    return back()->with('error', 'Stok tidak mencukupi saat proses penyimpanan.');
                }
                if ($purpose === 'jual') {
                    $totalSale += ((int) $item['qty']) * $hargaJualUnit;
                }
            }

            if ($purpose === 'jual' && $totalSale > 0) {
                $categoryId = $this->categoryPenjualanBarangId();
                DB::table('pemasukans')->insert([
                    'nominal' => (int) $totalSale,
                    'tanggal' => $request->tanggal_transaksi,
                    'keterangan' => 'Penjualan barang (Transaksi ' . $request->kode_transaksi . ')',
                    'category_pemasukan_id' => $categoryId,
                    'referense_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            return redirect()->route('transaksi-stock-out.index')->with('success', 'Transaksi Stok Keluar berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function show(Transaksi $transaksi): View
    {
        $transaksi->load('details.barang.unit_satuan', 'details.ownerUser');
        return view('transaksi-stock-out.show', compact('transaksi'));
    }

    public function edit(Transaksi $transaksi): View
    {
        $transaksi->load('details.ownerUser');
        $barangs = Barang::orderBy('nama_barang')->get();
        $investorOwners = $this->investorOwners();
        return view('transaksi-stock-out.edit', compact('transaksi', 'barangs', 'investorOwners'));
    }

    public function update(Request $request, Transaksi $transaksi): RedirectResponse
    {

        $request->validate([
            'tanggal_transaksi' => 'required|date',
            'kode_transaksi' => 'required|string|unique:transaksi,kode_transaksi,' . $transaksi->id,
            'cart_items_json' => 'required|json'
        ]);

        DB::beginTransaction();
        try {
            foreach ($transaksi->details as $detail) {
                [$ownerType, $ownerUserId] = $this->normalizeOwner($detail->owner_type ?? 'office', $detail->owner_user_id ?? null);
                InventoryStockService::increase((int) $detail->barang_id, $ownerType, $ownerUserId, (int) $detail->jumlah);
            }

            $transaksi->details()->delete();

            $transaksi->update([
                'tanggal_transaksi' => $request->tanggal_transaksi,
                'keterangan' => $request->keterangan,
            ]);

            $cartItems = json_decode($request->cart_items_json, true);
            $purposes = [];
            $targets = [];
            foreach ($cartItems as $item) {
                $purposes[] = strtolower(trim((string) ($item['purpose'] ?? 'umum')));
                $targets[] = (int) ($item['target_pelanggan_id'] ?? 0);
            }
            $uniquePurposes = array_values(array_unique(array_filter($purposes)));
            if (count($uniquePurposes) !== 1) {
                DB::rollBack();
                return back()->with('error', 'Kategori tujuan transaksi harus satu jenis.');
            }
            $purpose = $uniquePurposes[0] ?? 'umum';
            $targetPelangganId = null;
            if ($purpose === 'repair_pelanggan') {
                $uniqueTargets = array_values(array_unique(array_filter($targets)));
                if (count($uniqueTargets) !== 1) {
                    DB::rollBack();
                    return back()->with('error', 'Repair pelanggan wajib memilih satu pelanggan yang sama untuk semua item.');
                }
                $targetPelangganId = (int) $uniqueTargets[0];
            }

            foreach ($cartItems as $item) {
                [$ownerType, $ownerUserId] = $this->normalizeOwner($item['owner_type'] ?? 'office', $item['owner_user_id'] ?? null);
                if ($ownerType === 'investor' && in_array($purpose, ['jual', 'repair_umum', 'repair_pelanggan'], true)) {
                    DB::rollBack();
                    return back()->with('error', 'Barang milik investor tidak boleh untuk jual atau repair.');
                }
                if ($ownerType !== 'office' && $purpose === 'jual') {
                    DB::rollBack();
                    return back()->with('error', 'Kategori tujuan jual hanya untuk barang milik kantor.');
                }
                if ($ownerType !== 'office' && in_array($purpose, ['repair_umum', 'repair_pelanggan'], true)) {
                    DB::rollBack();
                    return back()->with('error', 'Kategori tujuan repair hanya untuk barang milik kantor.');
                }
                $stockQty = InventoryStockService::getOwnerQty((int) $item['id'], $ownerType, $ownerUserId);
                if ($stockQty < (int) $item['qty']) {
                    $barang = Barang::find($item['id']);
                    DB::rollBack();
                    return back()->with('error', "Stok '{$barang->nama_barang}' tidak mencukupi untuk diperbarui.");
                }
            }

            $totalSale = 0;
            foreach ($cartItems as $item) {
                [$ownerType, $ownerUserId] = $this->normalizeOwner($item['owner_type'] ?? 'office', $item['owner_user_id'] ?? null);
                $pricing = InventoryStockService::getOwnerPricing((int) $item['id'], $ownerType, $ownerUserId);
                $hppUnit = (int) ($pricing['hpp_unit'] ?? 0);
                $hargaJualUnit = (int) ($pricing['harga_jual_unit'] ?? 0);
                TransaksiDetail::create([
                    'transaksi_id' => $transaksi->id,
                    'barang_id' => $item['id'],
                    'owner_type' => $ownerType,
                    'owner_user_id' => $ownerUserId,
                    'jumlah' => $item['qty'],
                    'hpp_unit' => $hppUnit,
                    'harga_jual_unit' => $hargaJualUnit,
                    'purpose' => $purpose,
                    'purpose_scope' => $purpose === 'repair_pelanggan' ? 'pelanggan' : ($purpose === 'repair_umum' ? 'umum' : null),
                    'target_pelanggan_id' => $targetPelangganId,
                ]);
                $ok = InventoryStockService::decrease((int) $item['id'], $ownerType, $ownerUserId, (int) $item['qty']);
                if (!$ok) {
                    DB::rollBack();
                    return back()->with('error', 'Stok tidak mencukupi saat proses update.');
                }
                if ($purpose === 'jual') {
                    $totalSale += ((int) $item['qty']) * $hargaJualUnit;
                }
            }

            if ($purpose === 'jual') {
                DB::table('pemasukans')
                    ->where('keterangan', 'Penjualan barang (Transaksi ' . $transaksi->kode_transaksi . ')')
                    ->delete();
                if ($totalSale > 0) {
                    $categoryId = $this->categoryPenjualanBarangId();
                    DB::table('pemasukans')->insert([
                        'nominal' => (int) $totalSale,
                        'tanggal' => $request->tanggal_transaksi,
                        'keterangan' => 'Penjualan barang (Transaksi ' . $transaksi->kode_transaksi . ')',
                        'category_pemasukan_id' => $categoryId,
                        'referense_id' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('transaksi-stock-out.index')->with('success', 'Transaksi berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memperbarui transaksi: ' . $e->getMessage());
        }
    }

    public function destroy(Transaksi $transaksi): RedirectResponse
    {

        DB::beginTransaction();
        try {
            foreach ($transaksi->details as $detail) {
                [$ownerType, $ownerUserId] = $this->normalizeOwner($detail->owner_type ?? 'office', $detail->owner_user_id ?? null);
                InventoryStockService::increase((int) $detail->barang_id, $ownerType, $ownerUserId, (int) $detail->jumlah);
            }

            DB::table('pemasukans')
                ->where('keterangan', 'Penjualan barang (Transaksi ' . $transaksi->kode_transaksi . ')')
                ->delete();
            $transaksi->delete();

            DB::commit();
            return redirect()->route('transaksi-stock-out.index')->with('success', 'Transaksi berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus transaksi: ' . $e->getMessage());
        }
    }

    public function exportPdf()
    {

        $transaksis = Transaksi::where('jenis_transaksi', 'out')->with('user')->get();
        $namaPerusahaan = 'Ramdan';
        $tanggalCetak = Carbon::now()->format('d F Y');

        $pdf = Pdf::loadView('transaksi-stock-out.export-pdf', compact('transaksis', 'namaPerusahaan', 'tanggalCetak'));
        return $pdf->stream('laporan-stok-keluar.pdf');
    }

    public function exportItemPdf(Transaksi $transaksi)
    {
        $transaksi->load('details.barang.unit_satuan', 'details.ownerUser', 'user');
        $namaPembuat = Auth::user()->name;
        $pdf = Pdf::loadView('transaksi-stock-out.export-item-pdf', compact('transaksi', 'namaPembuat'));
        return $pdf->stream('transaksi-' . $transaksi->kode_transaksi . '.pdf');
    }

    public function ownerStock(Request $request): JsonResponse
    {
        $barangId = (int) $request->query('barang_id');
        [$ownerType, $ownerUserId] = $this->normalizeOwner($request->query('owner_type'), $request->query('owner_user_id'));
        if ($barangId < 1) {
            return response()->json(['qty' => 0]);
        }
        $qty = InventoryStockService::getOwnerQty($barangId, $ownerType, $ownerUserId);
        return response()->json(['qty' => $qty]);
    }

    private function investorOwners()
    {
        return DB::table('users')
            ->leftJoin('investor_share_rules', 'users.id', '=', 'investor_share_rules.user_id')
            ->leftJoin('model_has_roles', function ($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', '=', 'App\\Models\\User');
            })
            ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where(function ($q) {
                $q->whereNotNull('investor_share_rules.id')
                    ->orWhereRaw('LOWER(roles.name) like ?', ['%investor%'])
                    ->orWhereRaw('LOWER(roles.name) like ?', ['%mitra%']);
            })
            ->select('users.id', 'users.name')
            ->distinct()
            ->orderBy('users.name')
            ->get();
    }

    private function normalizeOwner($ownerType, $ownerUserId): array
    {
        $type = strtolower(trim((string) $ownerType));
        if ($type !== 'investor') {
            return ['office', null];
        }
        $uid = (int) $ownerUserId;
        return ['investor', $uid > 0 ? $uid : null];
    }

    private function ownerName(?int $ownerUserId): ?string
    {
        if (empty($ownerUserId)) {
            return null;
        }
        return DB::table('users')->where('id', (int) $ownerUserId)->value('name');
    }

    private function categoryPenjualanBarangId(): ?int
    {
        if (!DB::getSchemaBuilder()->hasTable('category_pemasukans')) {
            return null;
        }
        $name = 'Penjualan Barang';
        $row = DB::table('category_pemasukans')->where('nama_kategori_pemasukan', $name)->first();
        if ($row) {
            return (int) $row->id;
        }
        return (int) DB::table('category_pemasukans')->insertGetId([
            'nama_kategori_pemasukan' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
