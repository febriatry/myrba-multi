<?php

namespace App\Http\Controllers;

use App\Models\TiketAduan;
use App\Http\Requests\{StoreTiketAduanRequest, UpdateTiketAduanRequest};
use Yajra\DataTables\Facades\DataTables;
use Image;

class TiketAduanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:tiket aduan view')->only('index', 'show');
        $this->middleware('permission:tiket aduan create')->only('create', 'store');
        $this->middleware('permission:tiket aduan edit')->only('edit', 'update');
        $this->middleware('permission:tiket aduan delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->ajax()) {
            $tiketAduans = TiketAduan::with('pelanggan:id,nama');

            return Datatables::of($tiketAduans)
                ->addIndexColumn()
                ->addColumn('deskripsi_aduan', function ($row) {
                    return str($row->deskripsi_aduan)->limit(100);
                })
                ->addColumn('pelanggan', function ($row) {
                    return $row->pelanggan ? $row->pelanggan->nama : '';
                })
                ->addColumn('lampiran', function ($row) {
                    if ($row->lampiran == null) {
                        return 'https://dummyimage.com/350x350/cccccc/000000&text=No+Image';
                    }
                    return asset('storage/uploads/lampirans/' . $row->lampiran);
                })

                ->addColumn('action', 'tiket-aduans.include.action')
                ->toJson();
        }

        return view('tiket-aduans.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('tiket-aduans.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTiketAduanRequest $request)
    {
        $attr = $request->validated();

        // --- Generate nomor_tiket ---
        $tahun = date('Y');

        $lastTicket = TiketAduan::where('nomor_tiket', 'like', "TKT-$tahun-%")
            ->orderByDesc('nomor_tiket')
            ->first();

        if ($lastTicket) {
            // Ambil 6 digit terakhir sebagai nomor urut
            $lastNumber = (int)substr($lastTicket->nomor_tiket, -6);
            $nextNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '000001';
        }

        $attr['nomor_tiket'] = "TKT-$tahun-$nextNumber";

        // --- Handle file upload ---
        if ($request->file('lampiran') && $request->file('lampiran')->isValid()) {
            $path = storage_path('app/public/uploads/lampirans/');
            $filename = $request->file('lampiran')->hashName();

            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            Image::make($request->file('lampiran')->getRealPath())->resize(500, 500, function ($constraint) {
                $constraint->upsize();
                $constraint->aspectRatio();
            })->save($path . $filename);

            $attr['lampiran'] = $filename;
        }

        // --- Simpan tiket ---
        TiketAduan::create($attr);

        return redirect()
            ->route('tiket-aduans.index')
            ->with('success', __('The tiketAduan was created successfully.'));
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TiketAduan $tiketAduan
     * @return \Illuminate\Http\Response
     */
    public function show(TiketAduan $tiketAduan)
    {
        $tiketAduan->load('pelanggan:id,nama');

        return view('tiket-aduans.show', compact('tiketAduan'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\TiketAduan $tiketAduan
     * @return \Illuminate\Http\Response
     */
    public function edit(TiketAduan $tiketAduan)
    {
        $tiketAduan->load('pelanggan:id,coverage_area');

        return view('tiket-aduans.edit', compact('tiketAduan'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TiketAduan $tiketAduan
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateTiketAduanRequest $request, TiketAduan $tiketAduan)
    {
        $attr = $request->validated();

        if ($request->file('lampiran') && $request->file('lampiran')->isValid()) {

            $path = storage_path('app/public/uploads/lampirans/');
            $filename = $request->file('lampiran')->hashName();

            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            Image::make($request->file('lampiran')->getRealPath())->resize(500, 500, function ($constraint) {
                $constraint->upsize();
                $constraint->aspectRatio();
            })->save($path . $filename);

            // delete old lampiran from storage
            if ($tiketAduan->lampiran != null && file_exists($path . $tiketAduan->lampiran)) {
                unlink($path . $tiketAduan->lampiran);
            }

            $attr['lampiran'] = $filename;
        }

        $tiketAduan->update($attr);

        return redirect()
            ->route('tiket-aduans.index')
            ->with('success', __('The tiketAduan was updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TiketAduan $tiketAduan
     * @return \Illuminate\Http\Response
     */
    public function destroy(TiketAduan $tiketAduan)
    {
        try {
            $path = storage_path('app/public/uploads/lampirans/');

            if ($tiketAduan->lampiran != null && file_exists($path . $tiketAduan->lampiran)) {
                unlink($path . $tiketAduan->lampiran);
            }

            $tiketAduan->delete();

            return redirect()
                ->route('tiket-aduans.index')
                ->with('success', __('The tiketAduan was deleted successfully.'));
        } catch (\Throwable $th) {
            return redirect()
                ->route('tiket-aduans.index')
                ->with('error', __("The tiketAduan can't be deleted because it's related to another table."));
        }
    }
}
