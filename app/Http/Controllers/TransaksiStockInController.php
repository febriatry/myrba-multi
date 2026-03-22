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

class TransaksiStockInController extends Controller
{
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            $transaksis = Transaksi::where('jenis_transaksi', 'in')
                ->with('user');

            return DataTables::of($transaksis)
                ->addIndexColumn()
                ->addColumn('user_name', fn($transaksi) => $transaksi->user->name)
                ->addColumn('action', fn($transaksi) => view('transaksi-stock-in.include.action', compact('transaksi')))
                ->toJson();
        }
        return view('transaksi-stock-in.index');
    }

    public function create(): View
    {
        $barangs = Barang::orderBy('nama_barang')->get();
        $investorOwners = $this->investorOwners();
        return view('transaksi-stock-in.create', compact('barangs', 'investorOwners'));
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
            $transaksi = Transaksi::create([
                'user_id' => Auth::id(),
                'kode_transaksi' => $request->kode_transaksi,
                'tanggal_transaksi' => $request->tanggal_transaksi,
                'jenis_transaksi' => 'in',
                'keterangan' => $request->keterangan,
            ]);

            $cartItems = json_decode($request->cart_items_json, true);

            foreach ($cartItems as $item) {
                [$ownerType, $ownerUserId] = $this->normalizeOwner($item['owner_type'] ?? 'office', $item['owner_user_id'] ?? null);
                $hppUnit = (int) ($item['hpp_unit'] ?? 0);
                $hargaJualUnit = (int) ($item['harga_jual_unit'] ?? 0);
                if ($hppUnit < 0 || $hargaJualUnit < 0) {
                    throw new \RuntimeException('Harga tidak valid.');
                }
                TransaksiDetail::create([
                    'transaksi_id' => $transaksi->id,
                    'barang_id' => $item['id'],
                    'owner_type' => $ownerType,
                    'owner_user_id' => $ownerUserId,
                    'jumlah' => $item['qty'],
                    'hpp_unit' => $hppUnit,
                    'harga_jual_unit' => $hargaJualUnit,
                ]);
                InventoryStockService::increaseWithPricing((int) $item['id'], $ownerType, $ownerUserId, (int) $item['qty'], $hppUnit, $hargaJualUnit);
            }

            DB::commit();
            return redirect()->route('transaksi-stock-in.index')->with('success', 'Transaksi stock Masuk berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function show(Transaksi $transaksi): View
    {
        $transaksi->load('details.barang.unit_satuan', 'details.ownerUser');
        return view('transaksi-stock-in.show', compact('transaksi'));
    }

    public function edit(Transaksi $transaksi): View
    {
        $transaksi->load('details.ownerUser');
        $barangs = Barang::orderBy('nama_barang')->get();
        $investorOwners = $this->investorOwners();
        return view('transaksi-stock-in.edit', compact('transaksi', 'barangs', 'investorOwners'));
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
                InventoryStockService::decrease((int) $detail->barang_id, $ownerType, $ownerUserId, (int) $detail->jumlah);
            }

            $transaksi->details()->delete();

            $transaksi->update([
                'tanggal_transaksi' => $request->tanggal_transaksi,
                'keterangan' => $request->keterangan,
            ]);

            $cartItems = json_decode($request->cart_items_json, true);
            foreach ($cartItems as $item) {
                [$ownerType, $ownerUserId] = $this->normalizeOwner($item['owner_type'] ?? 'office', $item['owner_user_id'] ?? null);
                $hppUnit = (int) ($item['hpp_unit'] ?? 0);
                $hargaJualUnit = (int) ($item['harga_jual_unit'] ?? 0);
                if ($hppUnit < 0 || $hargaJualUnit < 0) {
                    throw new \RuntimeException('Harga tidak valid.');
                }
                TransaksiDetail::create([
                    'transaksi_id' => $transaksi->id,
                    'barang_id' => $item['id'],
                    'owner_type' => $ownerType,
                    'owner_user_id' => $ownerUserId,
                    'jumlah' => $item['qty'],
                    'hpp_unit' => $hppUnit,
                    'harga_jual_unit' => $hargaJualUnit,
                ]);
                InventoryStockService::increaseWithPricing((int) $item['id'], $ownerType, $ownerUserId, (int) $item['qty'], $hppUnit, $hargaJualUnit);
            }

            DB::commit();
            return redirect()->route('transaksi-stock-in.index')->with('success', 'Transaksi berhasil diperbarui.');
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
                InventoryStockService::decrease((int) $detail->barang_id, $ownerType, $ownerUserId, (int) $detail->jumlah);
            }

            $transaksi->delete();

            DB::commit();
            return redirect()->route('transaksi-stock-in.index')->with('success', 'Transaksi berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus transaksi: ' . $e->getMessage());
        }
    }

    public function exportPdf()
    {
        $transaksis = Transaksi::where('jenis_transaksi', 'in')->with('user')->get();
        $namaPerusahaan = 'Ramdan';
        $tanggalCetak = Carbon::now()->format('d F Y');

        $pdf = Pdf::loadView('transaksi-stock-in.export-pdf', compact('transaksis', 'namaPerusahaan', 'tanggalCetak'));
        return $pdf->stream('laporan-stock-masuk.pdf');
    }

    public function exportItemPdf(Transaksi $transaksi)
    {
        $transaksi->load('details.barang.unit_satuan', 'details.ownerUser', 'user');
        $namaPembuat = Auth::user()->name;
        $pdf = Pdf::loadView('transaksi-stock-in.export-item-pdf', compact('transaksi', 'namaPembuat'));
        return $pdf->stream('transaksi-' . $transaksi->kode_transaksi . '.pdf');
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
}
