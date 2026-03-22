<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Barang;
use App\Exports\LaporanBarangExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LaporanBarangController extends Controller
{
    public function index(Request $request)
    {
        // Mengambil SEMUA barang untuk dropdown filter
        $barangs = Barang::orderBy('nama_barang', 'asc')->get(['id', 'nama_barang']);
        $laporan = [];
        $filters = [
            'tanggal_mulai' => $request->query('tanggal_mulai'),
            'tanggal_selesai' => $request->query('tanggal_selesai'),
            'barang_id' => $request->query('barang_id'),
        ];

        if (!empty($filters['tanggal_mulai']) || !empty($filters['tanggal_selesai']) || !empty($filters['barang_id'])) {
            $maxRangeDays = 90;
            $validator = Validator::make($filters, [
                'tanggal_mulai' => 'required|date',
                'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
                'barang_id' => ['nullable', 'exists:barang,id'],
            ]);
            $validator->after(function ($validator) use ($filters, $maxRangeDays) {
                if (!empty($filters['tanggal_mulai']) && !empty($filters['tanggal_selesai'])) {
                    $tanggalMulai = Carbon::parse($filters['tanggal_mulai']);
                    $tanggalSelesai = Carbon::parse($filters['tanggal_selesai']);
                    if ($tanggalMulai->diffInDays($tanggalSelesai) > $maxRangeDays) {
                        $validator->errors()->add('tanggal_selesai', "Rentang tanggal laporan maksimal {$maxRangeDays} hari.");
                    }
                }
            });

            if ($validator->fails()) {
                return redirect()->route('laporan-barang.index')
                    ->withErrors($validator)
                    ->withInput();
            }

            $laporan = $this->buildLaporan(
                (string) $filters['tanggal_mulai'],
                (string) $filters['tanggal_selesai'],
                !empty($filters['barang_id']) ? (int) $filters['barang_id'] : null
            );
        }

        return view('laporan-barang.index', compact('barangs', 'laporan', 'filters'));
    }

    public function exportExcel(Request $request)
    {
        $maxRangeDays = 90; // Batas maksimal rentang laporan adalah 90 hari

        $validator = Validator::make($request->all(), [
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'barang_id' => ['nullable', 'exists:barang,id'],
        ]);

        $validator->after(function ($validator) use ($request, $maxRangeDays) {
            if ($request->filled(['tanggal_mulai', 'tanggal_selesai'])) {
                $tanggalMulai = Carbon::parse($request->tanggal_mulai);
                $tanggalSelesai = Carbon::parse($request->tanggal_selesai);
                if ($tanggalMulai->diffInDays($tanggalSelesai) > $maxRangeDays) {
                    $validator->errors()->add('tanggal_selesai', "Rentang tanggal laporan maksimal {$maxRangeDays} hari.");
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->route('laporan-barang.index')
                ->withErrors($validator)
                ->withInput();
        }

        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');
        $barangId = $request->input('barang_id');
        $startDate = Carbon::parse($tanggalMulai)->format('Y-m-d');
        $endDate = Carbon::parse($tanggalSelesai)->format('Y-m-d');

        $filterDesc = $barangId ? 'barang-' . $barangId : 'semua-barang';
        $fileName = "laporan-stok_{$filterDesc}_{$startDate}_sd_{$endDate}.xlsx";

        return Excel::download(new LaporanBarangExport($tanggalMulai, $tanggalSelesai, $barangId), $fileName);
    }

    private function buildLaporan(string $tanggalMulai, string $tanggalSelesai, ?int $barangId): array
    {
        $saldoAwalQuery = DB::table('transaksi_details as td')
            ->join('transaksi as t', 'td.transaksi_id', '=', 't.id')
            ->leftJoin('users as u', 'td.owner_user_id', '=', 'u.id')
            ->select(
                'td.barang_id',
                'td.owner_type',
                'td.owner_user_id',
                DB::raw("CASE WHEN td.owner_type = 'investor' THEN CONCAT('Investor: ', COALESCE(u.name, '-')) ELSE 'Kantor' END as owner_label"),
                DB::raw("SUM(CASE WHEN t.jenis_transaksi = 'in' THEN td.jumlah ELSE 0 END) as total_masuk"),
                DB::raw("SUM(CASE WHEN t.jenis_transaksi = 'out' THEN td.jumlah ELSE 0 END) as total_keluar")
            )
            ->where('t.tanggal_transaksi', '<', $tanggalMulai)
            ->groupBy('td.barang_id', 'td.owner_type', 'td.owner_user_id', 'u.name');

        if (!empty($barangId)) {
            $saldoAwalQuery->where('td.barang_id', $barangId);
        }

        $transaksiQuery = DB::table('transaksi_details as td')
            ->join('transaksi as t', 'td.transaksi_id', '=', 't.id')
            ->join('barang as b', 'td.barang_id', '=', 'b.id')
            ->leftJoin('users as u', 'td.owner_user_id', '=', 'u.id')
            ->select(
                'td.barang_id',
                'b.nama_barang',
                'td.owner_type',
                'td.owner_user_id',
                DB::raw("CASE WHEN td.owner_type = 'investor' THEN CONCAT('Investor: ', COALESCE(u.name, '-')) ELSE 'Kantor' END as owner_label"),
                't.tanggal_transaksi',
                't.kode_transaksi',
                't.keterangan',
                'td.hpp_unit',
                'td.harga_jual_unit',
                DB::raw("CASE WHEN t.jenis_transaksi = 'in' THEN td.jumlah ELSE 0 END as masuk"),
                DB::raw("CASE WHEN t.jenis_transaksi = 'out' THEN td.jumlah ELSE 0 END as keluar")
            )
            ->whereBetween('t.tanggal_transaksi', [$tanggalMulai, $tanggalSelesai]);

        if (!empty($barangId)) {
            $transaksiQuery->where('td.barang_id', $barangId);
        }

        $saldoAwalData = $saldoAwalQuery->get()->mapWithKeys(function ($row) {
            $key = (int) $row->barang_id . '|' . (string) $row->owner_type . '|' . (string) ($row->owner_user_id ?? '');
            return [$key => $row];
        });
        $transaksiData = $transaksiQuery->orderBy('b.nama_barang')->orderBy('t.tanggal_transaksi')->get();

        $laporan = [];
        $saldoSaatIni = [];

        $grouped = $transaksiData->groupBy(function ($row) {
            return (int) $row->barang_id . '|' . (string) $row->owner_type . '|' . (string) ($row->owner_user_id ?? '');
        });

        foreach ($grouped as $groupKey => $transaksis) {
            $awalRow = $saldoAwalData[$groupKey] ?? null;
            $saldoAwal = ((int) ($awalRow->total_masuk ?? 0)) - ((int) ($awalRow->total_keluar ?? 0));
            $saldoSaatIni[$groupKey] = $saldoAwal;

            $namaBarang = $transaksis->first()->nama_barang;
            $ownerLabel = $transaksis->first()->owner_label ?? ($awalRow->owner_label ?? 'Kantor');

            $laporan[] = (object) [
                'nama_barang_header' => $namaBarang,
                'owner_label' => $ownerLabel,
                'saldo_awal' => $saldoAwal,
                'is_header' => true,
            ];

            $no = 0;
            foreach ($transaksis as $transaksi) {
                $no++;
                $saldoSaatIni[$groupKey] += (int) ($transaksi->masuk ?? 0) - (int) ($transaksi->keluar ?? 0);
                $laporan[] = (object) [
                    'no' => $no,
                    'tanggal_transaksi' => $transaksi->tanggal_transaksi,
                    'kode_transaksi' => $transaksi->kode_transaksi,
                    'keterangan' => $transaksi->keterangan,
                    'hpp_unit' => (int) ($transaksi->hpp_unit ?? 0),
                    'harga_jual_unit' => (int) ($transaksi->harga_jual_unit ?? 0),
                    'masuk' => (int) ($transaksi->masuk ?? 0),
                    'keluar' => (int) ($transaksi->keluar ?? 0),
                    'saldo_akhir' => (int) $saldoSaatIni[$groupKey],
                    'is_header' => false,
                ];
            }
        }

        return $laporan;
    }
}
