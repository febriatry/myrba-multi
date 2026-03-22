<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvestorInventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'web', 'permission:investor view'])->only('index');
    }

    public function index()
    {
        $userId = (int) Auth::id();
        $stocks = DB::table('barang_owner_stocks')
            ->join('barang', 'barang_owner_stocks.barang_id', '=', 'barang.id')
            ->leftJoin('unit_satuan', 'barang.unit_satuan_id', '=', 'unit_satuan.id')
            ->leftJoin('kategori_barang', 'barang.kategori_barang_id', '=', 'kategori_barang.id')
            ->where('barang_owner_stocks.owner_type', 'investor')
            ->where('barang_owner_stocks.owner_user_id', $userId)
            ->select(
                'barang.id',
                'barang.kode_barang',
                'barang.nama_barang',
                'unit_satuan.nama_unit_satuan',
                'kategori_barang.nama_kategori_barang',
                'barang_owner_stocks.qty',
                'barang_owner_stocks.harga_jual_unit',
                DB::raw('(barang_owner_stocks.qty * barang_owner_stocks.harga_jual_unit) as total_nilai')
            )
            ->orderBy('barang.nama_barang')
            ->get();

        $installAgg = DB::table('transaksi_details as td')
            ->join('transaksi as t', 'td.transaksi_id', '=', 't.id')
            ->join('barang as b', 'td.barang_id', '=', 'b.id')
            ->leftJoin('unit_satuan', 'b.unit_satuan_id', '=', 'unit_satuan.id')
            ->leftJoin('kategori_barang', 'b.kategori_barang_id', '=', 'kategori_barang.id')
            ->where('td.owner_type', 'investor')
            ->where('td.owner_user_id', $userId)
            ->where('t.jenis_transaksi', 'out')
            ->where('td.purpose', 'install')
            ->groupBy('td.barang_id', 'b.kode_barang', 'b.nama_barang', 'unit_satuan.nama_unit_satuan', 'kategori_barang.nama_kategori_barang')
            ->select(
                'td.barang_id',
                'b.kode_barang',
                'b.nama_barang',
                'unit_satuan.nama_unit_satuan',
                'kategori_barang.nama_kategori_barang',
                DB::raw('SUM(td.jumlah) as qty_install'),
                DB::raw('SUM(td.jumlah * td.harga_jual_unit) as nilai_install')
            )
            ->get()
            ->keyBy('barang_id');

        $returnAgg = DB::table('transaksi_details as td')
            ->join('transaksi as t', 'td.transaksi_id', '=', 't.id')
            ->where('td.owner_type', 'investor')
            ->where('td.owner_user_id', $userId)
            ->where('t.jenis_transaksi', 'in')
            ->where('td.purpose', 'return_device')
            ->groupBy('td.barang_id')
            ->select(
                'td.barang_id',
                DB::raw('SUM(td.jumlah) as qty_return'),
                DB::raw('SUM(td.jumlah * td.harga_jual_unit) as nilai_return')
            )
            ->get()
            ->keyBy('barang_id');

        $deployed = [];
        foreach ($installAgg as $barangId => $row) {
            $ret = $returnAgg->get($barangId);
            $qty = (int) ($row->qty_install ?? 0) - (int) ($ret->qty_return ?? 0);
            $nilai = (int) ($row->nilai_install ?? 0) - (int) ($ret->nilai_return ?? 0);
            if ($qty <= 0 && $nilai <= 0) {
                continue;
            }
            $deployed[] = (object) [
                'barang_id' => (int) $barangId,
                'kode_barang' => $row->kode_barang,
                'nama_barang' => $row->nama_barang,
                'nama_unit_satuan' => $row->nama_unit_satuan,
                'nama_kategori_barang' => $row->nama_kategori_barang,
                'qty' => $qty,
                'total_nilai' => max(0, $nilai),
            ];
        }
        $deployed = collect($deployed)->sortBy('nama_barang')->values();

        $summary = [
            'stock_total_qty' => (int) $stocks->sum('qty'),
            'stock_total_nilai' => (int) $stocks->sum('total_nilai'),
            'deployed_total_qty' => (int) $deployed->sum('qty'),
            'deployed_total_nilai' => (int) $deployed->sum('total_nilai'),
        ];
        $summary['total_qty'] = $summary['stock_total_qty'] + $summary['deployed_total_qty'];
        $summary['total_nilai'] = $summary['stock_total_nilai'] + $summary['deployed_total_nilai'];

        return view('investor-inventory.index', compact('stocks', 'deployed', 'summary'));
    }
}
