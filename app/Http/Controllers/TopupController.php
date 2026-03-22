<?php

namespace App\Http\Controllers;

use App\Models\Topup;
use App\Http\Requests\{StoreTopupRequest, UpdateTopupRequest};
use Yajra\DataTables\Facades\DataTables;
use App\Models\BalanceHistory;
use App\Models\CategoryPemasukan;
use App\Models\Pemasukan;
use App\Models\Pelanggan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class TopupController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:topup view')->only('index', 'show');
        $this->middleware('permission:topup create')->only('create', 'store');
        $this->middleware('permission:topup edit')->only('edit', 'update');
        $this->middleware('permission:topup delete')->only('destroy');
        $this->middleware('permission:topup approval')->only('approve');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->ajax()) {
            $allowedAreas = getAllowedAreaCoverageIdsForUser();
            $topups = Topup::with('pelanggan:id,nama,no_layanan', 'bankAccount.bank:id,nama_bank')
                ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                    $q->whereHas('pelanggan', function ($p) use ($allowedAreas) {
                        $p->whereIn('coverage_area', $allowedAreas);
                    });
                });

            return DataTables::of($topups)
                ->addIndexColumn()
                ->addColumn('pelanggan', fn($row) => $row->pelanggan ? $row->pelanggan->nama . ' (' . $row->pelanggan->no_layanan . ')' : '-')
                ->addColumn('nominal', fn($row) => rupiah($row->nominal))
                ->addColumn('metode', fn($row) => ucfirst($row->metode)) // Menampilkan metode
                ->addColumn('bank', fn($row) => $row->bankAccount->bank->nama_bank ?? $row->metode_topup) // Menampilkan bank atau metode tripay
                ->addColumn('status', function ($row) {
                    $colors = ['pending' => 'warning', 'success' => 'success', 'failed' => 'danger', 'canceled' => 'secondary', 'refunded' => 'info', 'expired' => 'dark'];
                    return '<span class="badge bg-' . ($colors[$row->status] ?? 'secondary') . '">' . ucfirst($row->status) . '</span>';
                })
                ->addColumn('action', 'topups.include.action') // Menggunakan file action.blade.php
                ->rawColumns(['status', 'action'])
                ->toJson();
        }

        return view('topups.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return redirect()
            ->route('topups.index')
            ->with('error', __('Top up hanya dapat dilakukan melalui payment gateway Tripay.'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTopupRequest $request)
    {
        return redirect()
            ->route('topups.index')
            ->with('error', __('Top up hanya dapat dilakukan melalui payment gateway Tripay.'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Topup  $topup
     * @return \Illuminate\Http\Response
     */
    public function show(Topup $topup)
    {
        $topup->load('pelanggan:id,coverage_area');

        return view('topups.show', compact('topup'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Topup  $topup
     * @return \Illuminate\Http\Response
     */
    public function edit(Topup $topup)
    {
        return redirect()
            ->route('topups.index')
            ->with('error', __('Top up hanya dapat dilakukan melalui payment gateway Tripay.'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Topup  $topup
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateTopupRequest $request, Topup $topup)
    {
        return redirect()
            ->route('topups.index')
            ->with('error', __('Top up hanya dapat dilakukan melalui payment gateway Tripay.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Topup  $topup
     * @return \Illuminate\Http\Response
     */
    public function destroy(Topup $topup)
    {
        try {
            $topup->delete();

            return redirect()
                ->route('topups.index')
                ->with('success', __('The topup was deleted successfully.'));
        } catch (\Throwable $th) {
            return redirect()
                ->route('topups.index')
                ->with('error', __("The topup can't be deleted because it's related to another table."));
        }
    }

    public function approve(Request $request)
    {
        return response()->json(['message' => 'Top up manual dinonaktifkan. Konfirmasi saldo dilakukan otomatis melalui callback Tripay.'], 403);
    }
}
