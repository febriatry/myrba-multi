<?php

namespace App\Http\Controllers;

use App\Services\InventoryStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PelangganDeviceReturnBulkController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:pelanggan return device bulk'])->only('create', 'store');
    }

    public function create()
    {
        $investorOwners = $this->investorOwners();
        $inventoryRows = DB::table('barang_owner_stocks as bos')
            ->join('barang as b', 'bos.barang_id', '=', 'b.id')
            ->select('bos.owner_type', 'bos.owner_user_id', 'b.id', 'b.kode_barang', 'b.nama_barang')
            ->distinct()
            ->orderBy('b.nama_barang')
            ->get();

        return view('pelanggans.return-device-bulk', [
            'investorOwners' => $investorOwners,
            'inventoryRows' => $inventoryRows,
            'bulkErrors' => session('bulk_errors', []),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'owner_type' => 'required|in:office,investor',
            'owner_user_id' => 'nullable|integer',
            'status_return' => 'required|in:Berhasil,Gagal',
            'no_layanan_list' => 'required|string',
            'notes' => 'nullable|string|max:500',
            'items' => 'nullable|array',
            'items.*.barang_id' => 'required|integer',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.condition' => 'required|in:Good,Scrap',
        ]);

        $ownerType = strtolower(trim((string) $validated['owner_type']));
        $ownerUserId = $ownerType === 'investor' ? (int) ($validated['owner_user_id'] ?? 0) : null;
        if ($ownerType === 'investor' && (empty($ownerUserId) || $ownerUserId < 1)) {
            return redirect()->back()->withInput()->with('error', 'Investor/Mitra wajib dipilih.');
        }

        $statusReturn = (string) $validated['status_return'];
        $notes = $validated['notes'] ?? null;
        $itemsInput = $validated['items'] ?? [];

        if ($statusReturn === 'Berhasil' && empty($itemsInput)) {
            return redirect()->back()->withInput()->with('error', 'Minimal 1 barang return harus dipilih jika status Berhasil.');
        }

        $lines = preg_split("/\r\n|\n|\r/", (string) $validated['no_layanan_list']);
        $lines = array_values(array_filter(array_map(fn ($x) => trim((string) $x), $lines)));
        $noList = array_values(array_unique($lines));
        if (empty($noList)) {
            return redirect()->back()->withInput()->with('error', 'Daftar no layanan kosong.');
        }

        $pelangganRows = DB::table('pelanggans')
            ->whereIn('no_layanan', $noList)
            ->select('id', 'no_layanan', 'nama', 'status_berlangganan')
            ->get()
            ->keyBy('no_layanan');

        $preparedItems = [];
        if ($statusReturn === 'Berhasil') {
            foreach ($itemsInput as $it) {
                $barangId = (int) ($it['barang_id'] ?? 0);
                $qty = (int) ($it['qty'] ?? 0);
                $condition = (string) ($it['condition'] ?? 'Good');
                if ($barangId < 1 || $qty < 1) {
                    continue;
                }
                $pricing = InventoryStockService::getOwnerPricing($barangId, $ownerType, $ownerUserId ?: null);
                $hppUnit = (int) ($pricing['hpp_unit'] ?? 0);
                $hargaJualUnit = (int) ($pricing['harga_jual_unit'] ?? 0);
                if ($hppUnit < 1 || $hargaJualUnit < 1) {
                    return redirect()->back()->withInput()->with('error', 'Harga belum diset untuk salah satu barang pada inventory pemilik yang dipilih.');
                }
                $namaBarang = (string) (DB::table('barang')->where('id', $barangId)->value('nama_barang') ?? '');
                $preparedItems[] = [
                    'barang_id' => $barangId,
                    'nama_barang' => $namaBarang,
                    'qty' => $qty,
                    'condition' => $condition,
                    'owner_type' => $ownerType,
                    'owner_user_id' => $ownerUserId ?: null,
                    'hpp_unit' => $hppUnit,
                    'harga_jual_unit' => $hargaJualUnit,
                ];
            }
            if (empty($preparedItems)) {
                return redirect()->back()->withInput()->with('error', 'Barang return tidak valid.');
            }
        }

        $errors = [];
        $successCount = 0;

        foreach ($noList as $no) {
            $p = $pelangganRows->get($no);
            if (!$p) {
                $errors[] = $no . ' - tidak ditemukan';
                continue;
            }
            if (($p->status_berlangganan ?? '') !== 'Non Aktif') {
                $errors[] = $no . ' - status bukan Non Aktif';
                continue;
            }
            $exists = DB::table('pelanggan_device_returns')
                ->where('pelanggan_id', (int) $p->id)
                ->where('is_cancelled', 'No')
                ->exists();
            if ($exists) {
                $errors[] = $no . ' - sudah ada return sebelumnya';
                continue;
            }

            try {
                DB::transaction(function () use ($p, $statusReturn, $notes, $preparedItems, $ownerType, $ownerUserId, &$successCount) {
                    $returnId = (int) DB::table('pelanggan_device_returns')->insertGetId([
                        'pelanggan_id' => (int) $p->id,
                        'status_return' => $statusReturn,
                        'items' => $statusReturn === 'Berhasil' ? json_encode($preparedItems) : null,
                        'notes' => $notes,
                        'transaksi_in_id' => null,
                        'created_by' => auth()->id(),
                        'is_cancelled' => 'No',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    if ($statusReturn === 'Berhasil') {
                        $kode = 'TR-IN-RET-' . date('YmdHis') . '-' . (int) $p->id . '-' . $returnId;
                        $transaksiInId = (int) DB::table('transaksi')->insertGetId([
                            'user_id' => auth()->id(),
                            'kode_transaksi' => $kode,
                            'tanggal_transaksi' => now()->toDateString(),
                            'jenis_transaksi' => 'in',
                            'keterangan' => 'Return perangkat pelanggan ' . ($p->nama ?? '-') . ' (' . ($p->no_layanan ?? '-') . ')',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        foreach ($preparedItems as $it) {
                            if (($it['condition'] ?? '') !== 'Good') {
                                continue;
                            }
                            $barangId = (int) $it['barang_id'];
                            $qty = (int) $it['qty'];
                            $hppUnit = (int) $it['hpp_unit'];
                            $hargaJualUnit = (int) $it['harga_jual_unit'];

                            DB::table('transaksi_details')->insert([
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
                                'target_pelanggan_id' => (int) $p->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            InventoryStockService::increaseWithPricing($barangId, $ownerType, $ownerUserId, $qty, $hppUnit, $hargaJualUnit);
                        }

                        DB::table('pelanggan_device_returns')->where('id', $returnId)->update([
                            'transaksi_in_id' => $transaksiInId,
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('pelanggans')->where('id', (int) $p->id)->update([
                        'status_berlangganan' => 'Putus',
                        'updated_at' => now(),
                    ]);
                    $successCount++;
                });
            } catch (\Throwable $e) {
                $errors[] = $no . ' - gagal: ' . $e->getMessage();
            }
        }

        $msg = 'Bulk return selesai. Berhasil: ' . $successCount . ', gagal: ' . count($errors) . '.';
        $redirect = redirect()->route('pelanggans.return-device.bulk.create')->with('success', $msg);
        if (!empty($errors)) {
            $redirect = $redirect->with('bulk_errors', $errors);
        }
        return $redirect;
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

