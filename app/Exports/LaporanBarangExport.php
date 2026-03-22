<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;
use App\Models\Barang;

class LaporanBarangExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $tanggalMulai;
    protected $tanggalSelesai;
    protected $barangId;

    public function __construct($tanggalMulai, $tanggalSelesai, $barangId)
    {
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;
        $this->barangId = $barangId;
    }

    public function view(): View
    {
        // Subquery untuk menghitung saldo awal
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
            ->where('t.tanggal_transaksi', '<', $this->tanggalMulai)
            ->groupBy('td.barang_id', 'td.owner_type', 'td.owner_user_id', 'u.name');

        // Jika ada filter barang, terapkan juga di saldo awal
        if ($this->barangId) {
            $saldoAwalQuery->where('td.barang_id', $this->barangId);
        }

        // Ambil transaksi dalam rentang tanggal
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
            ->whereBetween('t.tanggal_transaksi', [$this->tanggalMulai, $this->tanggalSelesai]);

        if ($this->barangId) {
            $transaksiQuery->where('td.barang_id', $this->barangId);
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

            $laporan[] = (object)[
                'nama_barang_header' => $namaBarang,
                'owner_label' => $ownerLabel,
                'saldo_awal' => $saldoAwal,
                'is_header' => true
            ];

            $no = 0;
            foreach ($transaksis as $transaksi) {
                $no++;
                $saldoSaatIni[$groupKey] += $transaksi->masuk - $transaksi->keluar;
                $laporan[] = (object)[
                    'no' => $no,
                    'tanggal_transaksi' => $transaksi->tanggal_transaksi,
                    'kode_transaksi' => $transaksi->kode_transaksi,
                    'keterangan' => $transaksi->keterangan,
                    'hpp_unit' => (int) ($transaksi->hpp_unit ?? 0),
                    'harga_jual_unit' => (int) ($transaksi->harga_jual_unit ?? 0),
                    'masuk' => $transaksi->masuk,
                    'keluar' => $transaksi->keluar,
                    'saldo_akhir' => $saldoSaatIni[$groupKey],
                    'is_header' => false
                ];
            }
        }

        return view('laporan-barang.excel-template', [
            'laporan' => $laporan,
            'tanggalMulai' => $this->tanggalMulai,
            'tanggalSelesai' => $this->tanggalSelesai,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            2 => ['font' => ['bold' => true]],
        ];
    }
}
