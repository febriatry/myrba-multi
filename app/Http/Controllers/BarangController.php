<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Http\Requests\{StoreBarangRequest, UpdateBarangRequest};
use Yajra\DataTables\Facades\DataTables;
use Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarangController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:barang view')->only('index', 'show');
        $this->middleware('permission:barang create')->only('create', 'store');
        $this->middleware('permission:barang edit')->only('edit', 'update');
        $this->middleware('permission:barang delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->ajax()) {
            $barangs = DB::table('barang')
                ->leftJoin('unit_satuan', 'barang.unit_satuan_id', '=', 'unit_satuan.id')
                ->leftJoin('kategori_barang', 'barang.kategori_barang_id', '=', 'kategori_barang.id')
                ->select(
                    'barang.*',
                    'unit_satuan.nama_unit_satuan',
                    'kategori_barang.nama_kategori_barang',
                    DB::raw("(SELECT COUNT(1) FROM barang_owner_stocks bos0 WHERE bos0.barang_id = barang.id AND bos0.owner_type = 'office' AND bos0.owner_user_id IS NULL) as stock_office_rows"),
                    DB::raw("(SELECT COALESCE(SUM(bos1.qty), 0) FROM barang_owner_stocks bos1 WHERE bos1.barang_id = barang.id AND bos1.owner_type = 'office' AND bos1.owner_user_id IS NULL) as stock_office_calc"),
                    DB::raw("(SELECT COALESCE(SUM(bos.qty), 0) FROM barang_owner_stocks bos WHERE bos.barang_id = barang.id AND bos.owner_type = 'investor') as stock_investor"),
                    DB::raw("((SELECT COALESCE(SUM(bos3.qty), 0) FROM barang_owner_stocks bos3 WHERE bos3.barang_id = barang.id AND bos3.owner_type = 'office' AND bos3.owner_user_id IS NULL) + (SELECT COALESCE(SUM(bos4.qty), 0) FROM barang_owner_stocks bos4 WHERE bos4.barang_id = barang.id AND bos4.owner_type = 'investor')) as stock_total_calc")
                );

            return Datatables::of($barangs)
                ->addIndexColumn()
                ->addColumn('deskripsi_barang', function ($row) {
                    return str($row->deskripsi_barang)->limit(100);
                })
                ->addColumn('unit_satuan', function ($row) {
                    return $row->nama_unit_satuan ?? '';
                })
                ->addColumn('kategori_barang', function ($row) {
                    return $row->nama_kategori_barang ?? '';
                })
                ->addColumn('photo_barang', function ($row) {
                    if ($row->photo_barang == null) {
                       return 'https://dummyimage.com/350x350/cccccc/000000&text=No+Image';
                    }
                    return asset('storage/uploads/photo_barangs/' . $row->photo_barang);
                })
                ->addColumn('stock_office', function ($row) {
                    $hasOfficeRows = ((int) ($row->stock_office_rows ?? 0)) > 0;
                    return $hasOfficeRows ? (int) ($row->stock_office_calc ?? 0) : (int) ($row->stock ?? 0);
                })
                ->addColumn('stock_investor', function ($row) {
                    return (int) ($row->stock_investor ?? 0);
                })
                ->addColumn('stock_total', function ($row) {
                    $hasOfficeRows = ((int) ($row->stock_office_rows ?? 0)) > 0;
                    $office = $hasOfficeRows ? (int) ($row->stock_office_calc ?? 0) : (int) ($row->stock ?? 0);
                    return $office + (int) ($row->stock_investor ?? 0);
                })
                ->addColumn('action', 'barangs.include.action')
                ->toJson();
        }

        return view('barangs.index');
    }

    public function search(Request $request)
    {
        $term = strtolower(trim((string) $request->query('term', '')));
        $q = DB::table('barang')->select('id', 'kode_barang', 'nama_barang');
        if ($term !== '') {
            $q->where(function ($qq) use ($term) {
                $qq->whereRaw('LOWER(nama_barang) like ?', ['%' . $term . '%'])
                    ->orWhereRaw('LOWER(kode_barang) like ?', ['%' . $term . '%']);
            });
        }
        $rows = $q->orderBy('nama_barang')->limit(30)->get();
        return response()->json($rows);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('barangs.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreBarangRequest $request)
    {
        // 1. Ambil data yang sudah divalidasi
        $attr = $request->validated();

        $attr['stock'] = 0;

        if ($request->file('photo_barang') && $request->file('photo_barang')->isValid()) {
            $path = storage_path('app/public/uploads/photo_barangs/');
            $filename = $request->file('photo_barang')->hashName();

            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            Image::make($request->file('photo_barang')->getRealPath())->resize(500, 500, function ($constraint) {
                $constraint->upsize();
                $constraint->aspectRatio();
            })->save($path . $filename);

            // Tambahkan nama file ke array $attr
            $attr['photo_barang'] = $filename;
        }

        // 4. Buat data barang HANYA SEKALI dengan semua atribut yang sudah lengkap
        Barang::create($attr);

        return redirect()
            ->route('barangs.index')
            ->with('success', __('The barang was created successfully.'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Barang $barang
     * @return \Illuminate\Http\Response
     */
    public function show(Barang $barang)
    {
        $barang->load('unit_satuan:id', 'kategori_barang:id');

        return view('barangs.show', compact('barang'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Barang $barang
     * @return \Illuminate\Http\Response
     */
    public function edit(Barang $barang)
    {
        $barang->load('unit_satuan:id', 'kategori_barang:id');

        return view('barangs.edit', compact('barang'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Barang $barang
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateBarangRequest $request, Barang $barang)
    {
        $attr = $request->validated();

        if ($request->file('photo_barang') && $request->file('photo_barang')->isValid()) {

            $path = storage_path('app/public/uploads/photo_barangs/');
            $filename = $request->file('photo_barang')->hashName();

            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            Image::make($request->file('photo_barang')->getRealPath())->resize(500, 500, function ($constraint) {
                $constraint->upsize();
                $constraint->aspectRatio();
            })->save($path . $filename);

            // delete old photo_barang from storage
            if ($barang->photo_barang != null && file_exists($path . $barang->photo_barang)) {
                unlink($path . $barang->photo_barang);
            }

            $attr['photo_barang'] = $filename;
        }

        $barang->update($attr);

        return redirect()
            ->route('barangs.index')
            ->with('success', __('The barang was updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Barang $barang
     * @return \Illuminate\Http\Response
     */
    public function destroy(Barang $barang)
    {
        try {
            $path = storage_path('app/public/uploads/photo_barangs/');

            if ($barang->photo_barang != null && file_exists($path . $barang->photo_barang)) {
                unlink($path . $barang->photo_barang);
            }

            $barang->delete();

            return redirect()
                ->route('barangs.index')
                ->with('success', __('The barang was deleted successfully.'));
        } catch (\Throwable $th) {
            return redirect()
                ->route('barangs.index')
                ->with('error', __("The barang can't be deleted because it's related to another table."));
        }
    }
}
