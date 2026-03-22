<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Settingmikrotik;
use App\Http\Requests\{StorePackageRequest, UpdatePackageRequest};
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use \RouterOS\Client;
use \RouterOS\Query;


class PackageController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:package view')->only('index', 'show');
        $this->middleware('permission:package create')->only('create', 'store');
        $this->middleware('permission:package edit')->only('edit', 'update');
        $this->middleware('permission:package delete')->only('destroy');
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
            $packages = DB::table('packages')
                ->leftJoin('package_categories', 'packages.kategori_paket_id', '=', 'package_categories.id')
                ->select('packages.*', 'package_categories.nama_kategori')
                ->where('packages.tenant_id', $tenantId)
                ->where('package_categories.tenant_id', $tenantId)
                ->get();

            return DataTables::of($packages)
                ->addIndexColumn()
                ->addColumn('keterangan', function ($row) {
                    return str($row->keterangan)->limit(100);
                })
                ->addColumn('package_category', function ($row) {
                    return $row->nama_kategori;
                })->addColumn('action', 'packages.include.action')
                ->toJson();
        }

        return view('packages.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $routerProfiles = $this->getProfilesFromAllRouters();

        return view('packages.create', [
            'profile' => $routerProfiles['profiles'],
            'routers' => $routerProfiles['routers'],
            'profilesByRouter' => $routerProfiles['profilesByRouter'],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePackageRequest $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $attr = $request->validated();
        $attr['tenant_id'] = $tenantId;
        Package::create($attr);
        return redirect()
            ->route('packages.index')
            ->with('success', __('The package was created successfully.'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Package  $package
     * @return \Illuminate\Http\Response
     */
    public function show(Package $package)
    {
        $package->load('package_category:id,nama_kategori');

        return view('packages.show', compact('package'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Package  $package
     * @return \Illuminate\Http\Response
     */
    public function edit(Package $package)
    {
        $package->load('package_category:id,nama_kategori');
        $routerProfiles = $this->getProfilesFromAllRouters();

        return view('packages.edit', [
            'package' => $package,
            'profile' => $routerProfiles['profiles'],
            'routers' => $routerProfiles['routers'],
            'profilesByRouter' => $routerProfiles['profilesByRouter'],
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Package  $package
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePackageRequest $request, Package $package)
    {

        $package->update($request->validated());

        return redirect()
            ->route('packages.index')
            ->with('success', __('The package was updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Package  $package
     * @return \Illuminate\Http\Response
     */
    public function destroy(Package $package)
    {
        try {
            $package->delete();

            return redirect()
                ->route('packages.index')
                ->with('success', __('The package was deleted successfully.'));
        } catch (\Throwable $th) {
            return redirect()
                ->route('packages.index')
                ->with('error', __("The package can't be deleted because it's related to another table."));
        }
    }

    private function getProfilesFromAllRouters(): array
    {
        $routers = Settingmikrotik::select('id', 'identitas_router', 'host', 'username', 'password', 'port')->get();
        $profiles = [];
        $profilesByRouter = [];
        $routerOptions = [];
        foreach ($routers as $router) {
            $routerOptions[] = [
                'id' => $router->id,
                'identitas_router' => $router->identitas_router,
            ];
            $profilesByRouter[$router->id] = [];
            try {
                $client = new Client([
                    'host' => $router->host,
                    'user' => $router->username,
                    'pass' => $router->password,
                    'port' => (int) $router->port,
                ]);
                $query = new Query('/ppp/profile/print');
                $rows = $client->query($query)->read();
                foreach ($rows as $row) {
                    if (!empty($row['name'])) {
                        $profiles[$row['name']] = ['name' => $row['name']];
                        $profilesByRouter[$router->id][$row['name']] = ['name' => $row['name']];
                    }
                }
            } catch (\Throwable $e) {
                continue;
            }
        }
        $normalizedProfilesByRouter = [];
        foreach ($profilesByRouter as $routerId => $routerProfiles) {
            $normalizedProfilesByRouter[$routerId] = array_values($routerProfiles);
        }
        return [
            'routers' => $routerOptions,
            'profiles' => array_values($profiles),
            'profilesByRouter' => $normalizedProfilesByRouter,
        ];
    }
}
