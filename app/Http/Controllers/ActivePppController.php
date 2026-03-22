<?php

namespace App\Http\Controllers;

use App\Models\ActivePpp;
use App\Models\Settingmikrotik;
use App\Http\Requests\{StoreActivePppRequest, UpdateActivePppRequest};
use Yajra\DataTables\Facades\DataTables;
use \RouterOS\Client;
use \RouterOS\Query;
use Carbon\Carbon;

class ActivePppController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:active ppp view')->only('index', 'show');
        $this->middleware('permission:active ppp create')->only('create', 'store');
        $this->middleware('permission:active ppp edit')->only('edit', 'update');
        $this->middleware('permission:active ppp delete')->only('destroy');
    }

    public function index()
    {
        if (request()->ajax()) {
            $activePpps = [];
            $secretMap = [];
            $routers = Settingmikrotik::select('id', 'identitas_router', 'host', 'username', 'password', 'port')->get();
            foreach ($routers as $router) {
                try {
                    $client = new Client([
                        'host' => $router->host,
                        'user' => $router->username,
                        'pass' => $router->password,
                        'port' => (int) $router->port,
                    ]);
                    $query = new Query('/ppp/active/print');
                    $rows = $client->query($query)->read();
                    $secrets = $client->query(new Query('/ppp/secret/print'))->read();
                    foreach ($secrets as $s) {
                        $name = $s['name'] ?? null;
                        if ($name) {
                            $secretMap[$router->identitas_router . '|' . $name] = $s['last-logged-out'] ?? null;
                        }
                    }
                    foreach ($rows as $row) {
                        $row['router_name'] = $router->identitas_router;
                        $activePpps[] = $row;
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }
            return DataTables::of($activePpps)
                ->addIndexColumn()
                ->addColumn('last_disconnect', function ($row) use ($secretMap) {
                    $name = $row['name'] ?? null;
                    $router = $row['router_name'] ?? '';
                    $key = $router . '|' . $name;
                    $val = $name && isset($secretMap[$key]) ? $secretMap[$key] : null;
                    return $val ?: '-';
                })
                ->addColumn('action', 'active-ppps.include.action')
                ->toJson();
        }
        return view('active-ppps.index');
    }

    public function show($name)
    {
        $client = setRoute();
        $pppuser = (new Query('/ppp/secret/print'))
            ->where('name', $name);
        $pppuser = $client->query($pppuser)->read();


        $pppactive = (new Query('/ppp/active/print'))
            ->where('name', $name);
        $pppactive = $client->query($pppactive)->read();
        $lastLogout = isset($pppuser[0]['last-logged-out']) ? $pppuser[0]['last-logged-out'] : null;
        $downtime = '-';
        if (count($pppactive) == 0 && $lastLogout) {
            try {
                $last = \Carbon\Carbon::parse($lastLogout);
                $diff = $last->diff(\Carbon\Carbon::now());
                $hours = ($diff->d * 24) + $diff->h;
                $downtime = sprintf('%02d:%02d:%02d', $hours, $diff->i, $diff->s);
            } catch (\Exception $e) {
                $downtime = $lastLogout;
            }
        }
        return view('active-ppps.show', [
            'pppuser' => $pppuser,
            'pppactive' => $pppactive,
            'downtime' => $downtime
        ]);
    }

    public function destroy($id)
    {
        try {
            $client = setRoute();
            $queryDelete = (new Query('/ppp/active/remove'))
                ->equal('.id', $id);
            $client->query($queryDelete)->read();
            return redirect()
                ->route('active-ppps.index')
                ->with('success', __('The active PPP was deleted successfully.'));
        } catch (\Throwable $th) {
            return redirect()
                ->route('active-ppps.index')
                ->with('error', __("The active PPP can't be deleted because it's related to another table."));
        }
    }

    public function monitoring()
    {

        $interface = "<pppoe-" . $_GET["interface"] . ">";

        $client = setRoute();
        $query = (new Query('/interface/monitor-traffic'))
            ->equal('interface', $interface)
            ->equal('once', "");
        $getinterfacetraffic = $client->query($query)->read();
        $rows = array();
        $rows2 = array();
        $ftx = $getinterfacetraffic[0]['tx-bits-per-second'];
        $frx = $getinterfacetraffic[0]['rx-bits-per-second'];
        $rows['name'] = 'Tx';
        $rows['data'][] = $ftx;
        $rows2['name'] = 'Rx';
        $rows2['data'][] = $frx;
        $result = array();
        array_push($result, $rows);
        array_push($result, $rows2);
        print json_encode($result);
    }
}
