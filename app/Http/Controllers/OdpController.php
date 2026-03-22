<?php

namespace App\Http\Controllers;

use App\Models\Odp;
use App\Http\Requests\{StoreOdpRequest, UpdateOdpRequest};
use Yajra\DataTables\Facades\DataTables;
use Image;
use Illuminate\Support\Facades\DB;
use \RouterOS\Query;
use \RouterOS\Client;
use \RouterOS\Exceptions\ConnectException;

class OdpController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:odp view')->only('index', 'show');
        $this->middleware('permission:odp create')->only('create', 'store');
        $this->middleware('permission:odp edit')->only('edit', 'update');
        $this->middleware('permission:odp delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        if (request()->ajax()) {
            $allowedAreas = getAllowedAreaCoverageIdsForUser();
            $odps = DB::table('odps')
                ->leftJoin('odcs', 'odps.kode_odc', '=', 'odcs.id')
                ->leftJoin('area_coverages', 'odps.wilayah_odp', '=', 'area_coverages.id')
                ->select('odps.*', 'area_coverages.nama', 'odcs.kode_odc')
                ->where('odps.tenant_id', $tenantId)
                ->where('odcs.tenant_id', $tenantId)
                ->where('area_coverages.tenant_id', $tenantId)
                ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                    $q->whereIn('odps.wilayah_odp', $allowedAreas);
                })
                ->get();

            return Datatables::of($odps)
                ->addIndexColumn()
                ->addColumn('description', function ($row) {
                    return str($row->description)->limit(100);
                })
                ->addColumn('odc', function ($row) {
                    return $row->kode_odc;
                })->addColumn('area_coverage', function ($row) {
                    return $row->nama;
                })
                ->addColumn('document', function ($row) {
                    if ($row->document == null) {
                        return 'https://via.placeholder.com/350?text=No+Image+Avaiable';
                    }
                    return asset('storage/uploads/documents/' . $row->document);
                })

                ->addColumn('action', 'odps.include.action')
                ->toJson();
        }

        return view('odps.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('odps.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreOdpRequest $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $attr = $request->validated();
        $attr['tenant_id'] = $tenantId;

        if ($request->file('document') && $request->file('document')->isValid()) {

            $path = storage_path('app/public/uploads/documents/');
            $filename = $request->file('document')->hashName();

            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            Image::make($request->file('document')->getRealPath())->resize(500, 500, function ($constraint) {
                $constraint->upsize();
                $constraint->aspectRatio();
            })->save($path . $filename);

            $attr['document'] = $filename;
        }
        Odp::create($attr);

        return redirect()
            ->route('odps.index')
            ->with('success', __('The odp was created successfully.'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Odp $odp
     * @return \Illuminate\Http\Response
     */
    public function show(Odp $odp)
    {
        $odp->load('odc:id,kode_odc', 'area_coverage:id,kode_area');

        return view('odps.show', compact('odp'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Odp $odp
     * @return \Illuminate\Http\Response
     */
    public function edit(Odp $odp)
    {
        $odp->load('odc:id,kode_odc', 'area_coverage:id,kode_area');

        return view('odps.edit', compact('odp'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Odp $odp
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateOdpRequest $request, Odp $odp)
    {
        $attr = $request->validated();

        if ($request->file('document') && $request->file('document')->isValid()) {

            $path = storage_path('app/public/uploads/documents/');
            $filename = $request->file('document')->hashName();

            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            Image::make($request->file('document')->getRealPath())->resize(500, 500, function ($constraint) {
                $constraint->upsize();
                $constraint->aspectRatio();
            })->save($path . $filename);

            // delete old document from storage
            if ($odp->document != null && file_exists($path . $odp->document)) {
                unlink($path . $odp->document);
            }

            $attr['document'] = $filename;
        }

        $odp->update($attr);

        return redirect()
            ->route('odps.index')
            ->with('success', __('The odp was updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Odp $odp
     * @return \Illuminate\Http\Response
     */
    public function destroy(Odp $odp)
    {
        try {
            $path = storage_path('app/public/uploads/documents/');

            if ($odp->document != null && file_exists($path . $odp->document)) {
                unlink($path . $odp->document);
            }

            $odp->delete();

            return redirect()
                ->route('odps.index')
                ->with('success', __('The odp was deleted successfully.'));
        } catch (\Throwable $th) {
            return redirect()
                ->route('odps.index')
                ->with('error', __("The odp can't be deleted because it's related to another table."));
        }
    }

    public function odp($id)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $odc = DB::table('odcs')->select('wilayah_odc')->where('tenant_id', $tenantId)->where('id', $id)->first();
        if ($odc && !in_array((int) $odc->wilayah_odc, $allowedAreas, true)) {
            $message = 'Tidak diizinkan';
            $data = [];
            return response()->json(compact('message', 'data'), 403);
        }
        $data = DB::table('odps')
            ->where('tenant_id', $tenantId)
            ->where('kode_odc', $id)
            ->get();
        $message = 'Berhasil mengambil data kota';
        return response()->json(compact('message', 'data'));
    }

    public function getProfile($id)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $router = DB::table('settingmikrotiks')->where('tenant_id', $tenantId)->where('id', $id)->first();
        if (!$router) {
            return response()->json(['message' => 'Router tidak ditemukan'], 404);
        }
        try {
            $client = new Client([
                'host' => $router->host,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => (int) $router->port,
            ]);
        } catch (ConnectException $e) {
            echo $e->getMessage() . PHP_EOL;
            die();
        }
        $query = new Query('/ppp/secret/print');
        $data = $client->query($query)->read();
        $message = 'Berhasil mengambil data PPOE';
        return response()->json(compact('message', 'data'));
    }

    public function getStatic($id)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $router = DB::table('settingmikrotiks')->where('tenant_id', $tenantId)->where('id', $id)->first();
        if (!$router) {
            return response()->json(['message' => 'Router tidak ditemukan'], 404);
        }
        try {
            $client = new Client([
                'host' => $router->host,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => (int) $router->port,
            ]);
        } catch (ConnectException $e) {
            echo $e->getMessage() . PHP_EOL;
            die();
        }
        $query = (new Query('/queue/simple/print'))
            ->where('dynamic', 'false');
        $data = $client->query($query)->read();
        $message = 'Berhasil mengambil data statik';
        return response()->json(compact('message', 'data'));
    }

    public function getPort($id)
    {
        $data = DB::table('odps')->where('id', $id)->first();
        $jmlPort = $data->jumlah_port;

        $array = [];
        for ($x = 1; $x <=  $jmlPort; $x++) {
            // find customer
            $cek = DB::table('pelanggans')
                ->where('odp', $id)
                ->where('no_port_odp', $x)
                ->first();
            if ($cek) {
                $array[$x] = $cek->no_layanan . ' - ' . $cek->nama;
            } else {
                $array[$x] = 'Kosong';
            }
        }
        $message = 'Berhasil mengambil data kota';
        return response()->json(compact('message', 'array'));
    }
}
