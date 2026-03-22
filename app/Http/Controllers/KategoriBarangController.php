<?php

namespace App\Http\Controllers;

use App\Models\KategoriBarang;
use App\Http\Requests\{StoreKategoriBarangRequest, UpdateKategoriBarangRequest};
use Yajra\DataTables\Facades\DataTables;

class KategoriBarangController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:kategori barang view')->only('index', 'show');
        $this->middleware('permission:kategori barang create')->only('create', 'store');
        $this->middleware('permission:kategori barang edit')->only('edit', 'update');
        $this->middleware('permission:kategori barang delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->ajax()) {
            $kategoriBarangs = KategoriBarang::query();

            return DataTables::of($kategoriBarangs)
                ->addIndexColumn()
                ->addColumn('action', 'kategori-barangs.include.action')
                ->toJson();
        }

        return view('kategori-barangs.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('kategori-barangs.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreKategoriBarangRequest $request)
    {

        KategoriBarang::create($request->validated());

        return redirect()
            ->route('kategori-barangs.index')
            ->with('success', __('The kategoriBarang was created successfully.'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\KategoriBarang  $kategoriBarang
     * @return \Illuminate\Http\Response
     */
    public function show(KategoriBarang $kategoriBarang)
    {
        return view('kategori-barangs.show', compact('kategoriBarang'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\KategoriBarang  $kategoriBarang
     * @return \Illuminate\Http\Response
     */
    public function edit(KategoriBarang $kategoriBarang)
    {
        return view('kategori-barangs.edit', compact('kategoriBarang'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\KategoriBarang  $kategoriBarang
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateKategoriBarangRequest $request, KategoriBarang $kategoriBarang)
    {

        $kategoriBarang->update($request->validated());

        return redirect()
            ->route('kategori-barangs.index')
            ->with('success', __('The kategoriBarang was updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\KategoriBarang  $kategoriBarang
     * @return \Illuminate\Http\Response
     */
    public function destroy(KategoriBarang $kategoriBarang)
    {
        try {
            $kategoriBarang->delete();

            return redirect()
                ->route('kategori-barangs.index')
                ->with('success', __('The kategoriBarang was deleted successfully.'));
        } catch (\Throwable $th) {
            return redirect()
                ->route('kategori-barangs.index')
                ->with('error', __("The kategoriBarang can't be deleted because it's related to another table."));
        }
    }
}
