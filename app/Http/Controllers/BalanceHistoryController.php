<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BalanceHistory;
use App\Models\Pelanggan;
use Illuminate\Support\Carbon;
use Yajra\DataTables\Facades\DataTables;

class BalanceHistoryController extends Controller
{
    public function index(Request $request)
    {
        // Mengatur tanggal default jika tidak ada input dari user
        $start = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $end = $request->input('end_date', Carbon::now()->toDateString());

        if ($request->ajax()) {
            $query = BalanceHistory::with('pelanggan:id,nama,no_layanan')->select('balance_histories.*');

            $allowedAreas = getAllowedAreaCoverageIdsForUser();
            if (!empty($allowedAreas)) {
                $query->whereHas('pelanggan', function ($q) use ($allowedAreas) {
                    $q->whereIn('coverage_area', $allowedAreas);
                });
            } else {
                $query->whereRaw('1 = 0');
            }

            // Memastikan filter tanggal mencakup seluruh rentang waktu harian
            if ($request->filled('start_date')) {
                $query->where('created_at', '>=', $request->start_date . ' 00:00:00');
            }

            if ($request->filled('end_date')) {
                $query->where('created_at', '<=', $request->end_date . ' 23:59:59');
            }
            // AKHIR PERBAIKAN LOGIKA TANGGAL

            if ($request->filled('pelanggan_id')) {
                $query->where('pelanggan_id', $request->pelanggan_id);
            }

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('pelanggan_nama', fn($row) => optional($row->pelanggan)->nama . ' (' . optional($row->pelanggan)->no_layanan . ')')
                ->editColumn('amount', fn($row) => rupiah($row->amount))
                ->editColumn('balance_before', fn($row) => rupiah($row->balance_before))
                ->editColumn('balance_after', fn($row) => rupiah($row->balance_after))
                ->editColumn('created_at', fn($row) => Carbon::parse($row->created_at)->translatedFormat('d F Y H:i'))
                ->toJson();
        }

        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $pelanggans = Pelanggan::select('id', 'nama', 'no_layanan')
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('coverage_area', $allowedAreas);
            })->get();

        return view('balance-histories.index', compact('pelanggans', 'start', 'end'));
    }
}
