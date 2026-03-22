<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Services\InventoryStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PelangganDeviceReturnController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:pelanggan return device'])->only('create', 'store');
        $this->middleware(['auth', 'permission:pelanggan return device view'])->only('show');
        $this->middleware(['auth', 'permission:pelanggan return device cancel'])->only('cancel');
    }

    public function create(Pelanggan $pelanggan)
    {
        if (($pelanggan->status_berlangganan ?? '') !== 'Non Aktif') {
            return redirect()->route('pelanggans.show', (int) $pelanggan->id)->with('error', 'Return perangkat hanya untuk pelanggan dengan status Non Aktif.');
        }

        $candidates = $this->getReturnCandidates((int) $pelanggan->id);
        $investorOwners = $this->investorOwners();
        return view('pelanggans.return-device', [
            'pelanggan' => $pelanggan,
            'candidates' => $candidates,
            'investorOwners' => $investorOwners,
        ]);
    }

    public function store(Request $request, Pelanggan $pelanggan)
    {
        if (($pelanggan->status_berlangganan ?? '') !== 'Non Aktif') {
            return redirect()->route('pelanggans.show', (int) $pelanggan->id)->with('error', 'Return perangkat hanya untuk pelanggan dengan status Non Aktif.');
        }

        $validated = $request->validate([
            'status_return' => 'required|in:Berhasil,Gagal',
            'notes' => 'nullable|string|max:500',
            'items' => 'nullable|array',
            'items.*.barang_id' => 'required|integer',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.condition' => 'required|in:Good,Scrap',
            'items.*.owner_type' => 'nullable|in:office,investor',
            'items.*.owner_user_id' => 'nullable|integer',
        ]);

        $statusReturn = (string) $validated['status_return'];
        $notes = $validated['notes'] ?? null;
        $itemsInput = $validated['items'] ?? [];

        $candidates = $this->getReturnCandidates((int) $pelanggan->id)->keyBy('barang_id');

        $items = [];
        if ($statusReturn === 'Berhasil') {
            if (empty($itemsInput)) {
                return redirect()->back()->withInput()->with('error', 'Pilih minimal satu perangkat untuk return.');
            }
            foreach ($itemsInput as $it) {
                $barangId = (int) ($it['barang_id'] ?? 0);
                $qty = (int) ($it['qty'] ?? 0);
                $condition = (string) ($it['condition'] ?? 'Good');
                $cand = $candidates->get($barangId);
                $ownerType = strtolower(trim((string) ($it['owner_type'] ?? '')));
                $ownerType = $ownerType === 'investor' ? 'investor' : 'office';
                $ownerUserId = $ownerType === 'investor' ? (int) ($it['owner_user_id'] ?? 0) : null;
                $hppUnit = 0;
                $hargaJualUnit = 0;

                if ($cand) {
                    $maxQty = (int) ($cand->installed_qty ?? 0);
                    if ($qty < 1 || $qty > $maxQty) {
                        return redirect()->back()->withInput()->with('error', 'Qty return tidak valid untuk ' . ($cand->nama_barang ?? '-'));
                    }
                    $ownerType = (string) ($cand->owner_type ?? 'office');
                    $ownerUserId = $cand->owner_user_id ? (int) $cand->owner_user_id : null;
                    $pricing = InventoryStockService::getOwnerPricing($barangId, $ownerType, $ownerUserId);
                    $hppUnit = (int) ($pricing['hpp_unit'] ?? 0);
                    $hargaJualUnit = (int) ($pricing['harga_jual_unit'] ?? 0);
                    if ($hppUnit < 1 || $hargaJualUnit < 1) {
                        return redirect()->back()->withInput()->with('error', 'Harga belum diset pada inventory pemilik untuk barang ini.');
                    }
                } else {
                    $barang = DB::table('barang')->where('id', $barangId)->select('nama_barang')->first();
                    if (!$barang) {
                        return redirect()->back()->withInput()->with('error', 'Perangkat tidak valid.');
                    }
                    if ($ownerType === 'investor' && $ownerUserId < 1) {
                        return redirect()->back()->withInput()->with('error', 'Owner investor wajib dipilih.');
                    }
                    $pricing = InventoryStockService::getOwnerPricing($barangId, $ownerType, $ownerUserId ?: null);
                    $hppUnit = (int) ($pricing['hpp_unit'] ?? 0);
                    $hargaJualUnit = (int) ($pricing['harga_jual_unit'] ?? 0);
                    if ($hppUnit < 1 || $hargaJualUnit < 1) {
                        return redirect()->back()->withInput()->with('error', 'Harga belum diset pada inventory pemilik untuk barang ini.');
                    }
                }

                $items[] = [
                    'barang_id' => $barangId,
                    'nama_barang' => (string) ($cand->nama_barang ?? (DB::table('barang')->where('id', $barangId)->value('nama_barang') ?? '')),
                    'qty' => $qty,
                    'condition' => $condition,
                    'owner_type' => $ownerType,
                    'owner_user_id' => $ownerUserId ?: null,
                    'hpp_unit' => $hppUnit,
                    'harga_jual_unit' => $hargaJualUnit,
                ];
            }
        }

        try {
            DB::transaction(function () use ($pelanggan, $statusReturn, $notes, $items) {
                $returnId = (int) DB::table('pelanggan_device_returns')->insertGetId([
                    'pelanggan_id' => (int) $pelanggan->id,
                    'status_return' => $statusReturn,
                    'items' => !empty($items) ? json_encode($items) : null,
                    'notes' => $notes,
                    'transaksi_in_id' => null,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $transaksiInId = null;
                if ($statusReturn === 'Berhasil') {
                    $kode = 'TR-IN-RET-' . date('YmdHis') . '-' . (int) $pelanggan->id;
                    $transaksi = Transaksi::create([
                        'user_id' => auth()->id(),
                        'kode_transaksi' => $kode,
                        'tanggal_transaksi' => now()->toDateString(),
                        'jenis_transaksi' => 'in',
                        'keterangan' => 'Return perangkat pelanggan ' . ($pelanggan->nama ?? '-') . ' (' . ($pelanggan->no_layanan ?? '-') . ')',
                    ]);
                    $transaksiInId = (int) $transaksi->id;

                    foreach ($items as $it) {
                        $condition = (string) ($it['condition'] ?? 'Good');
                        if ($condition !== 'Good') {
                            continue;
                        }
                        $barangId = (int) $it['barang_id'];
                        $qty = (int) $it['qty'];
                        $ownerType = (string) ($it['owner_type'] ?? 'office');
                        $ownerUserId = !empty($it['owner_user_id']) ? (int) $it['owner_user_id'] : null;
                        $hppUnit = (int) ($it['hpp_unit'] ?? 0);
                        $hargaJualUnit = (int) ($it['harga_jual_unit'] ?? 0);

                        TransaksiDetail::create([
                            'transaksi_id' => $transaksiInId,
                            'barang_id' => $barangId,
                            'owner_type' => $ownerType,
                            'owner_user_id' => $ownerUserId,
                            'source_type' => 'pelanggan_return',
                            'source_id' => $returnId,
                            'jumlah' => $qty,
                            'hpp_unit' => $hppUnit,
                            'harga_jual_unit' => $hargaJualUnit,
                            'purpose' => 'return_device',
                            'purpose_scope' => 'pelanggan',
                            'target_pelanggan_id' => (int) $pelanggan->id,
                        ]);
                        InventoryStockService::increaseWithPricing($barangId, $ownerType, $ownerUserId, $qty, $hppUnit, $hargaJualUnit);
                    }
                }

                if ($transaksiInId) {
                    DB::table('pelanggan_device_returns')->where('id', $returnId)->update([
                        'transaksi_in_id' => $transaksiInId,
                        'updated_at' => now(),
                    ]);
                }

                DB::table('pelanggans')->where('id', (int) $pelanggan->id)->update([
                    'status_berlangganan' => 'Putus',
                    'updated_at' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan return: ' . $e->getMessage());
        }

        return redirect()->route('pelanggans.show', (int) $pelanggan->id)->with('success', 'Return perangkat tersimpan dan status pelanggan menjadi Putus.');
    }

    public function show(Pelanggan $pelanggan, $return)
    {
        $row = DB::table('pelanggan_device_returns')->where('id', (int) $return)->where('pelanggan_id', (int) $pelanggan->id)->first();
        if (!$row) {
            return redirect()->route('pelanggans.show', (int) $pelanggan->id)->with('error', 'Data return tidak ditemukan.');
        }
        $items = [];
        if (!empty($row->items)) {
            $decoded = json_decode($row->items, true);
            if (is_array($decoded)) {
                $items = $decoded;
            }
        }
        $createdByName = null;
        if (!empty($row->created_by)) {
            $createdByName = DB::table('users')->where('id', (int) $row->created_by)->value('name');
        }
        $cancelledByName = null;
        if (!empty($row->cancelled_by)) {
            $cancelledByName = DB::table('users')->where('id', (int) $row->cancelled_by)->value('name');
        }
        $transaksiKode = null;
        if (!empty($row->transaksi_in_id)) {
            $transaksiKode = DB::table('transaksi')->where('id', (int) $row->transaksi_in_id)->value('kode_transaksi');
        }

        return view('pelanggans.return-device-show', [
            'pelanggan' => $pelanggan,
            'row' => $row,
            'items' => $items,
            'createdByName' => $createdByName,
            'cancelledByName' => $cancelledByName,
            'transaksiKode' => $transaksiKode,
        ]);
    }

    public function cancel(Request $request, Pelanggan $pelanggan, $return)
    {
        $validated = $request->validate([
            'cancel_reason' => 'required|string|max:255',
        ]);
        $reason = trim((string) $validated['cancel_reason']);

        try {
            DB::transaction(function () use ($pelanggan, $return, $reason) {
                $row = DB::table('pelanggan_device_returns')
                    ->where('id', (int) $return)
                    ->where('pelanggan_id', (int) $pelanggan->id)
                    ->lockForUpdate()
                    ->first();
                if (!$row) {
                    throw new \RuntimeException('Data return tidak ditemukan.');
                }
                if (($row->is_cancelled ?? 'No') === 'Yes') {
                    throw new \RuntimeException('Return sudah dibatalkan.');
                }
                $createdAt = !empty($row->created_at) ? \Carbon\Carbon::parse($row->created_at) : null;
                if ($createdAt && $createdAt->diffInHours(now()) > 24) {
                    throw new \RuntimeException('Batas pembatalan adalah 24 jam setelah return dibuat.');
                }

                $items = [];
                if (!empty($row->items)) {
                    $decoded = json_decode($row->items, true);
                    if (is_array($decoded)) {
                        $items = $decoded;
                    }
                }

                if (($row->status_return ?? '') === 'Berhasil') {
                    foreach ($items as $it) {
                        if (($it['condition'] ?? '') !== 'Good') {
                            continue;
                        }
                        $barangId = (int) ($it['barang_id'] ?? 0);
                        $qty = (int) ($it['qty'] ?? 0);
                        if ($barangId < 1 || $qty < 1) {
                            continue;
                        }
                        $ownerType = (string) ($it['owner_type'] ?? 'office');
                        $ownerUserId = !empty($it['owner_user_id']) ? (int) $it['owner_user_id'] : null;
                        $ok = InventoryStockService::decrease($barangId, $ownerType, $ownerUserId, $qty);
                        if (!$ok) {
                            $barangName = DB::table('barang')->where('id', $barangId)->value('nama_barang');
                            throw new \RuntimeException('Stok tidak cukup untuk membatalkan return (' . ($barangName ?? '-') . ').');
                        }
                    }

                    $transaksiInId = (int) ($row->transaksi_in_id ?? 0);
                    if ($transaksiInId > 0) {
                        $cnt = (int) DB::table('transaksi_details')
                            ->where('transaksi_id', $transaksiInId)
                            ->count();
                        $ownedCnt = (int) DB::table('transaksi_details')
                            ->where('transaksi_id', $transaksiInId)
                            ->where('source_type', 'pelanggan_return')
                            ->where('source_id', (int) $row->id)
                            ->count();
                        if ($cnt !== $ownedCnt) {
                            throw new \RuntimeException('Transaksi IN return tidak murni milik return ini, tidak bisa dibatalkan.');
                        }
                        DB::table('transaksi_details')->where('transaksi_id', $transaksiInId)->delete();
                        DB::table('transaksi')->where('id', $transaksiInId)->delete();
                    }
                }

                DB::table('pelanggan_device_returns')->where('id', (int) $row->id)->update([
                    'is_cancelled' => 'Yes',
                    'cancelled_by' => auth()->id(),
                    'cancelled_at' => now(),
                    'cancel_reason' => $reason,
                    'updated_at' => now(),
                ]);

                $currentStatus = (string) (DB::table('pelanggans')->where('id', (int) $pelanggan->id)->value('status_berlangganan') ?? '');
                if ($currentStatus === 'Putus') {
                    DB::table('pelanggans')->where('id', (int) $pelanggan->id)->update([
                        'status_berlangganan' => 'Non Aktif',
                        'updated_at' => now(),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            return redirect()->route('pelanggans.return-device.show', [(int) $pelanggan->id, (int) $return])->with('error', 'Gagal membatalkan: ' . $e->getMessage());
        }

        return redirect()->route('pelanggans.show', (int) $pelanggan->id)->with('success', 'Return berhasil dibatalkan.');
    }

    private function getReturnCandidates(int $pelangganId)
    {
        $q = DB::table('transaksi_details as td')
            ->join('transaksi as t', 'td.transaksi_id', '=', 't.id')
            ->join('barang as b', 'td.barang_id', '=', 'b.id')
            ->where('t.jenis_transaksi', 'out')
            ->where(function ($qq) use ($pelangganId) {
                $qq->where(function ($q2) use ($pelangganId) {
                    $q2->where('td.purpose', 'install')->where('td.target_pelanggan_id', $pelangganId);
                })->orWhere(function ($q2) use ($pelangganId) {
                    $q2->where('td.source_type', 'pelanggan_request')->where('td.source_id', $pelangganId);
                });
            })
            ->where(function ($qq) {
                $qq->whereRaw('LOWER(b.nama_barang) like ?', ['%ont%'])
                    ->orWhereRaw('LOWER(b.nama_barang) like ?', ['%onu%'])
                    ->orWhereRaw('LOWER(b.nama_barang) like ?', ['%adaptor%'])
                    ->orWhereRaw('LOWER(b.nama_barang) like ?', ['%adapter%'])
                    ->orWhereRaw('LOWER(b.nama_barang) like ?', ['%adaptor 12v%'])
                    ->orWhereRaw('LOWER(b.nama_barang) like ?', ['%adapter 12v%'])
                    ->orWhereRaw('LOWER(b.nama_barang) like ?', ['%adaptor 5v%'])
                    ->orWhereRaw('LOWER(b.nama_barang) like ?', ['%adapter 5v%'])
                    ->orWhereRaw('LOWER(b.nama_barang) like ?', ['%htb a%'])
                    ->orWhereRaw('LOWER(b.nama_barang) like ?', ['%htb b%'])
                    ->orWhereRaw('LOWER(b.nama_barang) like ?', ['%danab%']);
            })
            ->groupBy('td.barang_id', 'b.nama_barang', 'td.owner_type', 'td.owner_user_id', 'td.hpp_unit', 'td.harga_jual_unit')
            ->select(
                'td.barang_id',
                'b.nama_barang',
                'td.owner_type',
                'td.owner_user_id',
                'td.hpp_unit',
                'td.harga_jual_unit',
                DB::raw('SUM(td.jumlah) as installed_qty')
            )
            ->orderBy('b.nama_barang');

        return $q->get();
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
}
