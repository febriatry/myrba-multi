<?php

namespace App\Http\Controllers;

use App\Models\UnitSatuan;
use App\Http\Requests\{StoreUnitSatuanRequest, UpdateUnitSatuanRequest};
use Yajra\DataTables\Facades\DataTables;

class UnitSatuanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:unit satuan view')->only('index', 'show');
        $this->middleware('permission:unit satuan create')->only('create', 'store');
        $this->middleware('permission:unit satuan edit')->only('edit', 'update');
        $this->middleware('permission:unit satuan delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->ajax()) {
            $unitSatuans = UnitSatuan::query();

            return DataTables::of($unitSatuans)
                ->addIndexColumn()
                ->addColumn('action', 'unit-satuans.include.action')
                ->toJson();
        }

        return view('unit-satuans.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('unit-satuans.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUnitSatuanRequest $request)
    {

        UnitSatuan::create($request->validated());

        return redirect()
            ->route('unit-satuans.index')
            ->with('success', __('The unitSatuan was created successfully.'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\UnitSatuan  $unitSatuan
     * @return \Illuminate\Http\Response
     */
    public function show(UnitSatuan $unitSatuan)
    {
        return view('unit-satuans.show', compact('unitSatuan'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\UnitSatuan  $unitSatuan
     * @return \Illuminate\Http\Response
     */
    public function edit(UnitSatuan $unitSatuan)
    {
        return view('unit-satuans.edit', compact('unitSatuan'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\UnitSatuan  $unitSatuan
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateUnitSatuanRequest $request, UnitSatuan $unitSatuan)
    {

        $unitSatuan->update($request->validated());

        return redirect()
            ->route('unit-satuans.index')
            ->with('success', __('The unitSatuan was updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\UnitSatuan  $unitSatuan
     * @return \Illuminate\Http\Response
     */
    public function destroy(UnitSatuan $unitSatuan)
    {
        try {
            $unitSatuan->delete();

            return redirect()
                ->route('unit-satuans.index')
                ->with('success', __('The unitSatuan was deleted successfully.'));
        } catch (\Throwable $th) {
            return redirect()
                ->route('unit-satuans.index')
                ->with('error', __("The unitSatuan can't be deleted because it's related to another table."));
        }
    }
}
