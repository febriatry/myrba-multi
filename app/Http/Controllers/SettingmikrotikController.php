<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSettingmikrotikRequest;
use App\Http\Requests\UpdateSettingmikrotikRequest;
use App\Models\Settingmikrotik;
use App\Services\TenantEntitlementService;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SettingmikrotikController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:settingmikrotik view')->only('index', 'show');
        $this->middleware('permission:settingmikrotik create')->only('create', 'store');
        $this->middleware('permission:settingmikrotik edit')->only('edit', 'update');
        $this->middleware('permission:settingmikrotik delete')->only('destroy');
    }

    public function index()
    {
        if (request()->ajax()) {
            $settingmikrotiks = Settingmikrotik::get();

            return DataTables::of($settingmikrotiks)
                ->addIndexColumn()
                ->addColumn('action', 'settingmikrotiks.include.action')
                ->rawColumns(['action'])
                ->toJson();
        }
        $countRouter = Settingmikrotik::count();

        return view('settingmikrotiks.index', [
            'countRouter' => $countRouter,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('settingmikrotiks.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSettingmikrotikRequest $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $max = TenantEntitlementService::quota('max_routers');
        if ($max !== null) {
            $current = (int) Settingmikrotik::query()->count();
            if ($current >= $max) {
                return redirect()->back()->withInput()->with('error', 'Kuota router telah habis. Maksimal: '.$max);
            }
        }
        $attr = $request->validated();
        $attr['tenant_id'] = $tenantId;
        $attr['password'] = $request->password;
        Settingmikrotik::create($attr);

        return redirect()
            ->route('settingmikrotiks.index')
            ->with('success', __('The settingmikrotik was created successfully.'));
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Settingmikrotik $settingmikrotik)
    {
        return view('settingmikrotiks.show', compact('settingmikrotik'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Settingmikrotik $settingmikrotik)
    {
        return view('settingmikrotiks.edit', compact('settingmikrotik'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSettingmikrotikRequest $request, Settingmikrotik $settingmikrotik)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $attr = $request->validated();
        if ($request->password == null) {
            DB::table('settingmikrotiks')
                ->where('id', $settingmikrotik->id)
                ->where('tenant_id', $tenantId)
                ->update([
                    'identitas_router' => $request->identitas_router,
                    'host' => $request->host,
                    'port' => $request->port,
                    'username' => $request->username,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        } else {
            DB::table('settingmikrotiks')
                ->where('id', $settingmikrotik->id)
                ->where('tenant_id', $tenantId)
                ->update([
                    'identitas_router' => $request->identitas_router,
                    'host' => $request->host,
                    'port' => $request->port,
                    'username' => $request->username,
                    'password' => $request->password,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }

        return redirect()
            ->route('settingmikrotiks.index')
            ->with('success', __('The settingmikrotik was updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Settingmikrotik $settingmikrotik)
    {
        try {
            $settingmikrotik->delete();

            return redirect()
                ->route('settingmikrotiks.index')
                ->with('success', __('The settingmikrotik was deleted successfully.'));
        } catch (\Throwable $th) {
            return redirect()
                ->route('settingmikrotiks.index')
                ->with('error', __("The settingmikrotik can't be deleted because it's related to another table."));
        }
    }
}
