<?php

namespace App\Http\Controllers;

use App\Models\Pemasukan;
use App\Http\Requests\{StorePemasukanRequest, UpdatePemasukanRequest};
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PemasukanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:pemasukan view')->only('index', 'show');
        $this->middleware('permission:pemasukan create')->only('create', 'store');
        $this->middleware('permission:pemasukan edit')->only('edit', 'update');
        $this->middleware('permission:pemasukan delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        if (request()->ajax()) {
            $pemasukans = DB::table('pemasukans')
                ->leftJoin('category_pemasukans', 'pemasukans.category_pemasukan_id', '=', 'category_pemasukans.id')
                ->leftJoin('tagihans', 'pemasukans.referense_id', '=', 'tagihans.id')
                ->select(
                    'pemasukans.*',
                    'category_pemasukans.nama_kategori_pemasukan',
                    'tagihans.status_bayar as tagihan_status_bayar',
                    'tagihans.tanggal_review as tagihan_tanggal_review',
                    'tagihans.reviewed_by as tagihan_reviewed_by'
                );
            $pemasukans
                ->where('pemasukans.tenant_id', $tenantId)
                ->where('category_pemasukans.tenant_id', $tenantId)
                ->where(function ($q) use ($tenantId) {
                    $q->whereNull('pemasukans.referense_id')->orWhere('tagihans.tenant_id', $tenantId);
                });
            $start_date = intval($request->query('start_date'));
            $end_date = intval($request->query('end_date'));
            $kategori_pemasukan = intval($request->query('kategori_pemasukan'));
            $metode_bayar = $request->query('metode_bayar');

            if (isset($start_date) && !empty($start_date)) {
                $from = date("Y-m-d H:i:s", substr($request->query('start_date'), 0, 10));
                $pemasukans = $pemasukans->where('pemasukans.tanggal', '>=', $from);
            } else {
                $from = date('Y-m-d') . " 00:00:00";
                $pemasukans = $pemasukans->where('pemasukans.tanggal', '>=', $from);
            }
            if (isset($end_date) && !empty($end_date)) {
                $to = date("Y-m-d H:i:s", substr($request->query('end_date'), 0, 10));
                $pemasukans = $pemasukans->where('pemasukans.tanggal', '<=', $to);
            } else {
                $to = date('Y-m-d') . " 23:59:59";
                $pemasukans = $pemasukans->where('pemasukans.tanggal', '<=', $to);
            }
            if (isset($kategori_pemasukan) && !empty($kategori_pemasukan)) {
                if ($kategori_pemasukan != 'All') {
                    $pemasukans = $pemasukans->where('pemasukans.category_pemasukan_id', $kategori_pemasukan);
                }
            }
            if (!empty($metode_bayar) && $metode_bayar !== 'All') {
                $pemasukans = $pemasukans->where('pemasukans.metode_bayar', $metode_bayar);
            }
            $pemasukans = $pemasukans->orderBy('pemasukans.id', 'DESC');

            return DataTables::of($pemasukans)
                ->addIndexColumn()
                ->addColumn('nominal', function ($row) {
                    return rupiah($row->nominal);
                })
                ->addColumn('nama_kategori_pemasukan', function ($row) {
                    return isset($row->nama_kategori_pemasukan) ? $row->nama_kategori_pemasukan : '-';
                })
                ->addColumn('keterangan', function ($row) {
                    return str($row->keterangan)->limit(100);
                })
                ->addColumn('action', 'pemasukans.include.action')
                ->toJson();
        }

        $from = date('Y-m-d') . " 00:00:00";
        $to = date('Y-m-d') . " 23:59:59";
        $microFrom = strtotime($from) * 1000;
        $microTo = strtotime($to) * 1000;
        $start_date = $request->query('start_date') !== null ? intval($request->query('start_date')) : $microFrom;
        $end_date = $request->query('end_date') !== null ? intval($request->query('end_date')) : $microTo;
        $kategori_pemasukan = $request->query('kategori_pemasukan') !== null ? intval($request->query('kategori_pemasukan')) : null;

        $categoryPemasukans = DB::table('category_pemasukans')->where('tenant_id', $tenantId)->get();
        return view('pemasukans.index', [
            'microFrom' => $start_date,
            'microTo' => $end_date,
            'kategori_pemasukan' => $kategori_pemasukan,
            'categoryPemasukans' => $categoryPemasukans,
        ]);
    }

    public function summary(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $start_date = $request->query('start_date');
        $end_date = $request->query('end_date');
        $kategori_pemasukan = $request->query('kategori_pemasukan');
        $metode_bayar = $request->query('metode_bayar');
        $query = DB::table('pemasukans')
            ->leftJoin('category_pemasukans', 'pemasukans.category_pemasukan_id', '=', 'category_pemasukans.id')
            ->select('pemasukans.id', 'pemasukans.nominal', 'pemasukans.metode_bayar', 'pemasukans.tanggal', 'pemasukans.category_pemasukan_id');
        $query->where('pemasukans.tenant_id', $tenantId)->where('category_pemasukans.tenant_id', $tenantId);
        if (!empty($start_date)) {
            $from = date("Y-m-d H:i:s", substr($start_date, 0, 10));
            $query->where('pemasukans.tanggal', '>=', $from);
        } else {
            $from = date('Y-m-d') . " 00:00:00";
            $query->where('pemasukans.tanggal', '>=', $from);
        }
        if (!empty($end_date)) {
            $to = date("Y-m-d H:i:s", substr($end_date, 0, 10));
            $query->where('pemasukans.tanggal', '<=', $to);
        } else {
            $to = date('Y-m-d') . " 23:59:59";
            $query->where('pemasukans.tanggal', '<=', $to);
        }
        if (!empty($kategori_pemasukan) && $kategori_pemasukan !== 'All') {
            $query->where('pemasukans.category_pemasukan_id', intval($kategori_pemasukan));
        }
        if (!empty($metode_bayar) && $metode_bayar !== 'All') {
            $query->where('pemasukans.metode_bayar', $metode_bayar);
        }
        $count = $query->count();
        $sum = $query->sum('pemasukans.nominal');
        return response()->json([
            'count' => $count,
            'sum' => rupiah($sum),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $categoryPemasukans = DB::table('category_pemasukans')->where('tenant_id', $tenantId)->get();
        return view('pemasukans.create', compact('categoryPemasukans'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePemasukanRequest $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $attr = $request->validated();
        $attr['tenant_id'] = $tenantId;
        Pemasukan::create($attr);

        return redirect()
            ->route('pemasukans.index')
            ->with('success', __('The pemasukan was created successfully.'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Pemasukan  $pemasukan
     * @return \Illuminate\Http\Response
     */
    public function show(Pemasukan $pemasukan)
    {
        return view('pemasukans.show', compact('pemasukan'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Pemasukan  $pemasukan
     * @return \Illuminate\Http\Response
     */
    public function edit(Pemasukan $pemasukan)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $categoryPemasukans = DB::table('category_pemasukans')->where('tenant_id', $tenantId)->get();
        return view('pemasukans.edit', compact('pemasukan', 'categoryPemasukans'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Pemasukan  $pemasukan
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePemasukanRequest $request, Pemasukan $pemasukan)
    {

        $pemasukan->update($request->validated());

        return redirect()
            ->route('pemasukans.index')
            ->with('success', __('The pemasukan was updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Pemasukan  $pemasukan
     * @return \Illuminate\Http\Response
     */
    public function destroy(Pemasukan $pemasukan)
    {
        try {
            if ($this->isLockedByValidatedTagihan((int) $pemasukan->id) && !auth()->user()?->can('pemasukan protected delete')) {
                return redirect()
                    ->route('pemasukans.index')
                    ->with('error', 'Pemasukan dari tagihan pelanggan yang sudah dibayar dan divalidasi tidak bisa dihapus tanpa izin khusus.');
            }
            $pemasukan->delete();

            return redirect()
                ->route('pemasukans.index')
                ->with('success', __('The pemasukan was deleted successfully.'));
        } catch (\Throwable $th) {
            return redirect()
                ->route('pemasukans.index')
                ->with('error', __("The pemasukan can't be deleted because it's related to another table."));
        }
    }

    private function isLockedByValidatedTagihan(int $pemasukanId): bool
    {
        return DB::table('pemasukans')
            ->join('tagihans', 'pemasukans.referense_id', '=', 'tagihans.id')
            ->where('pemasukans.id', $pemasukanId)
            ->where('tagihans.status_bayar', 'Sudah Bayar')
            ->where(function ($q) {
                $q->whereNotNull('tagihans.tanggal_review')
                    ->orWhereNotNull('tagihans.reviewed_by');
            })
            ->exists();
    }
}
