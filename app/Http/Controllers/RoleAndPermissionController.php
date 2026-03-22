<?php

namespace App\Http\Controllers;

use App\Http\Requests\{StoreRoleRequest, UpdateRoleRequest};
use Spatie\Permission\Models\{Role, Permission};
use Spatie\Permission\PermissionRegistrar;
use Yajra\DataTables\Facades\DataTables;
use App\Models\AreaCoverage;

class RoleAndPermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:role & permission view')->only('index', 'show');
        $this->middleware('permission:role & permission create')->only('create', 'store');
        $this->middleware('permission:role & permission edit')->only('edit', 'update');
        $this->middleware('permission:role & permission delete')->only('delete');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->ajax()) {
            $tenantId = (int) app(PermissionRegistrar::class)->getPermissionsTeamId();
            $users = Role::query()->where('tenant_id', $tenantId);

            return DataTables::of($users)
                ->addIndexColumn()
                ->addColumn('created_at', function ($row) {
                    return $row->created_at->format('d/m/Y H:i');
                })->addColumn('updated_at', function ($row) {
                    return $row->updated_at->format('d/m/Y H:i');
                })
                ->addColumn('action', 'roles.include.action')
                ->toJson();
        }

        return view('roles.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $areas = AreaCoverage::all();
        return view('roles.create', compact('areas'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRoleRequest $request)
    {
        $tenantId = (int) app(PermissionRegistrar::class)->getPermissionsTeamId();
        $role = Role::create(['name' => $request->name, 'guard_name' => 'web', 'tenant_id' => $tenantId]);

        foreach ($request->permissions as $perm) {
            Permission::findOrCreate($perm, 'web');
        }
        $role->givePermissionTo($request->permissions);

        return redirect()
            ->route('roles.index')
            ->with('success', __('The role was created successfully.'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(int $id)
    {
        $tenantId = (int) app(PermissionRegistrar::class)->getPermissionsTeamId();
        $role = Role::with('permissions')->where('tenant_id', $tenantId)->findOrFail($id);

        return view('roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(int $id)
    {
        $tenantId = (int) app(PermissionRegistrar::class)->getPermissionsTeamId();
        $role = Role::with('permissions')->where('tenant_id', $tenantId)->findOrFail($id);

        $areas = AreaCoverage::all();
        return view('roles.edit', compact('role', 'areas'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRoleRequest $request, $id)
    {
        $tenantId = (int) app(PermissionRegistrar::class)->getPermissionsTeamId();
        $role = Role::where('tenant_id', $tenantId)->findOrFail($id);

        $role->update(['name' => $request->name]);

        foreach ($request->permissions as $perm) {
            Permission::findOrCreate($perm, 'web');
        }
        $role->syncPermissions($request->permissions);

        return redirect()
            ->route('roles.index')
            ->with('success', __('The role was updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(int $id)
    {
        $tenantId = (int) app(PermissionRegistrar::class)->getPermissionsTeamId();
        $role = Role::withCount('users')->where('tenant_id', $tenantId)->findOrFail($id);

        // if any user where role.id = $id
        if ($role->users_count < 1) {
            $role->delete();

            return redirect()
                ->route('roles.index')
                ->with('success', __('The role was deleted successfully.'));
        } else {
            return redirect()
                ->route('roles.index')
                ->with('error', __('Can`t delete role.'));
        }

        return redirect()->route('role.index');
    }
}
