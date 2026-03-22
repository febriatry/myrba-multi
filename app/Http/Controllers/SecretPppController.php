<?php

namespace App\Http\Controllers;

use App\Models\SecretPpp;
use App\Models\Settingmikrotik;
use App\Http\Requests\{StoreSecretPppRequest, UpdateSecretPppRequest};
use Yajra\DataTables\Facades\DataTables;
use \RouterOS\Query;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class SecretPppController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:secret ppp view')->only('index', 'show');
        $this->middleware('permission:secret ppp create')->only('create', 'store');
        $this->middleware('permission:secret ppp edit')->only('edit', 'update');
        $this->middleware('permission:secret ppp delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->ajax()) {
            $secretPpps = [];
            $routers = Settingmikrotik::select('id', 'identitas_router', 'host', 'username', 'password', 'port')->get();
            foreach ($routers as $router) {
                try {
                    $client = new \RouterOS\Client([
                        'host' => $router->host,
                        'user' => $router->username,
                        'pass' => $router->password,
                        'port' => (int) $router->port,
                    ]);
                    $query = new Query('/ppp/secret/print');
                    $rows = $client->query($query)->read();
                    foreach ($rows as $row) {
                        $row['router_name'] = $router->identitas_router;
                        $row['router_id'] = $router->id;
                        $secretPpps[] = $row;
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }
            return DataTables::of($secretPpps)
                ->addIndexColumn()
                ->addColumn('id', function ($row) {
                    return str($row['.id']);
                })
                ->addColumn('action', 'secret-ppps.include.action')
                ->toJson();
        }

        return view('secret-ppps.index');
    }

    public function create()
    {
        $routers = Settingmikrotik::select('id', 'identitas_router')->orderBy('identitas_router')->get();
        $routerId = request()->query('router_id');
        $profiles = [];
        if (!empty($routerId) && is_numeric($routerId)) {
            $client = setRouteTagihanByPelanggan((int) $routerId);
            $query = new Query('/ppp/profile/print');
            $profiles = $client->query($query)->read();
        }
        return view('secret-ppps.create', [
            'routers' => $routers,
            'routerId' => $routerId,
            'profiles' => $profiles
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'router_id' => 'required|exists:settingmikrotiks,id',
                'username' => 'required|string|max:255',
                'password' => 'required|string|max:255',
                'service' => 'required|string|max:255',
                'profile' => 'required|string|max:255',
                'komentar' => 'required|string|max:255',
            ],
        );

        if ($validator->fails()) {
            return redirect()->back()->withInput($request->all())->withErrors($validator);
        }
        $client = setRouteTagihanByPelanggan((int) $request->router_id);
        $queryAdd = (new Query('/ppp/secret/add'))
            ->equal('name', $request->username)
            ->equal('password', $request->password)
            ->equal('service', $request->service)
            ->equal('profile', $request->profile)
            ->equal('comment',  $request->komentar);
        $client->query($queryAdd)->read();
        return redirect()
            ->route('secret-ppps.index')
            ->with('success', __('The secretPpp was created successfully.'));
    }

    public function show(SecretPpp $secretPpp)
    {
        return view('secret-ppps.show', compact('secretPpp'));
    }

    public function enable(Request $request, $id)
    {
        $routerId = $request->query('router_id');
        $client = !empty($routerId) && is_numeric($routerId)
            ? setRouteTagihanByPelanggan((int) $routerId)
            : setRoute();

        $existing = $client->query((new Query('/ppp/secret/print'))->where('.id', $id))->read();
        $existingComment = $existing[0]['comment'] ?? null;
        $comment = myrbaMergeMikrotikComment($existingComment, 'Enable PPP');
        $queryComment = (new Query('/ppp/secret/set'))
            ->equal('.id', $id)
            ->equal('comment', $comment);
        $client->query($queryComment)->read();

        // set enable
        $query = (new Query('/ppp/secret/enable'))
            ->equal('.id', $id);
        $client->query($query)->read();
        return redirect()
            ->route('secret-ppps.index')
            ->with('success', __('The Secret PPP was enable successfully.'));
    }

    public function disable(Request $request, $id, $name)
    {
        $routerId = $request->query('router_id');
        $client = !empty($routerId) && is_numeric($routerId)
            ? setRouteTagihanByPelanggan((int) $routerId)
            : setRoute();

        $existing = $client->query((new Query('/ppp/secret/print'))->where('.id', $id))->read();
        $existingComment = $existing[0]['comment'] ?? null;
        $comment = myrbaMergeMikrotikComment($existingComment, 'Disable PPP');
        $queryComment = (new Query('/ppp/secret/set'))
            ->equal('.id', $id)
            ->equal('comment', $comment);
        $client->query($queryComment)->read();

        // set disable
        $queryDisable = (new Query('/ppp/secret/disable'))
            ->equal('.id', $id);
        $client->query($queryDisable)->read();

        // get name
        $queryGet = (new Query('/ppp/active/print'))
            ->where('name', $name);
        $data = $client->query($queryGet)->read();
        if (!empty($data) && isset($data[0]['.id'])) {
            $idActive = $data[0]['.id'];
            $queryDelete = (new Query('/ppp/active/remove'))
                ->equal('.id', $idActive);
            $client->query($queryDelete)->read();
        }

        return redirect()
            ->route('secret-ppps.index')
            ->with('success', __('The Secret PPP was disable successfully.'));
    }

    public function deleteSecret(Request $request, $id, $name)
    {
        try {
            $routerId = $request->query('router_id');
            $client = !empty($routerId) && is_numeric($routerId)
                ? setRouteTagihanByPelanggan((int) $routerId)
                : setRoute();
            $queryDelete = (new Query('/ppp/secret/remove'))
                ->equal('.id', $id);
            $client->query($queryDelete)->read();
            // get id
            $queryGet = (new Query('/ppp/active/print'))
                ->where('name', $name);
            $data = $client->query($queryGet)->read();
            if ($data) {
                // remove session
                $idActive = $data[0]['.id'];
                $removeSession = (new Query('/ppp/active/remove'))
                    ->equal('.id', $idActive);
                $client->query($removeSession)->read();
            }

            return redirect()
                ->route('secret-ppps.index')
                ->with('success', __('The active PPP was deleted successfully.'));
        } catch (\Throwable $th) {
            return redirect()
                ->route('secret-ppps.index')
                ->with('error', __("The active PPP can't be deleted because it's related to another table."));
        }
    }
}
