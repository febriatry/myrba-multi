<?php

namespace App\Http\Controllers;

use App\Models\ProfilePppoe;
use App\Models\Settingmikrotik;
use Yajra\DataTables\Facades\DataTables;
use \RouterOS\Client;
use \RouterOS\Query;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfilePppoeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:profile pppoe view')->only('index', 'show');
        $this->middleware('permission:profile pppoe create')->only('create', 'store');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->ajax()) {
            $profile = [];
            $routers = Settingmikrotik::select('id', 'identitas_router', 'host', 'username', 'password', 'port')->get();
            foreach ($routers as $router) {
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
                        $row['router_name'] = $router->identitas_router;
                        $profile[] = $row;
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }
            return DataTables::of($profile)
                ->addIndexColumn()
                ->addColumn('action', 'profile-pppoes.include.action')
                ->toJson();
        }
        return view('profile-pppoes.index');
    }

    public function create()
    {
        $routers = Settingmikrotik::select('id', 'identitas_router')->orderBy('identitas_router')->get();
        return view('profile-pppoes.create', compact('routers'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'router_id' => 'required|exists:settingmikrotiks,id',
            'name' => 'required|string|max:255',
            'local_address' => 'nullable|string|max:255',
            'remote_address' => 'nullable|string|max:255',
            'rate_limit' => 'nullable|string|max:255',
            'parent_queue' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withInput($request->all())->withErrors($validator);
        }

        $router = Settingmikrotik::select('id', 'host', 'username', 'password', 'port')
            ->where('id', (int) $request->router_id)
            ->firstOrFail();

        $client = new Client([
            'host' => $router->host,
            'user' => $router->username,
            'pass' => $router->password,
            'port' => (int) $router->port,
        ]);

        $queryAdd = new Query('/ppp/profile/add');
        $queryAdd->equal('name', $request->name);
        if (!empty($request->local_address)) {
            $queryAdd->equal('local-address', $request->local_address);
        }
        if (!empty($request->remote_address)) {
            $queryAdd->equal('remote-address', $request->remote_address);
        }
        if (!empty($request->rate_limit)) {
            $queryAdd->equal('rate-limit', $request->rate_limit);
        }
        if (!empty($request->parent_queue)) {
            $queryAdd->equal('parent-queue', $request->parent_queue);
        }
        $queryAdd->equal('comment', myrbaBuildMikrotikComment('Create PPP Profile'));
        $client->query($queryAdd)->read();

        return redirect()
            ->route('profile-pppoes.index')
            ->with('success', __('Profile PPP berhasil ditambahkan.'));
    }

    public function show(ProfilePppoe $profilePppoe)
    {
        return view('profile-pppoes.show', compact('profilePppoe'));
    }
}
