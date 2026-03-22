<?php

namespace App\Http\Controllers;

use App\Models\ActivePpp;
use App\Models\Settingmikrotik;
use Yajra\DataTables\Facades\DataTables;
use \RouterOS\Query;

class ActiveNonPppController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:non active ppp view')->only('index');
    }

    public function index()
    {
        if (request()->ajax()) {
            $data = [];
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
                    $secretPpps = $client->query($query)->read();
                    $query = new Query('/ppp/active/print');
                    $activePpps = $client->query($query)->read();
                    $arrSecret = [];
                    foreach ($secretPpps as $value) {
                        $arrSecret[] = $value['name'];
                    }
                    $arrActive = [];
                    foreach ($activePpps as $value) {
                        $arrActive[] = $value['name'];
                    }
                    $notInArray2 = array_diff($arrSecret, $arrActive);
                    foreach ($notInArray2 as $value) {
                        $last = null;
                        foreach ($secretPpps as $s) {
                            if (($s['name'] ?? null) === $value) {
                                $last = $s['last-logged-out'] ?? null;
                                break;
                            }
                        }
                        $downtime = '-';
                        if ($last) {
                            try {
                                $lastDt = \Carbon\Carbon::parse($last);
                                $diff = $lastDt->diff(\Carbon\Carbon::now());
                                $hours = ($diff->d * 24) + $diff->h;
                                $downtime = sprintf('%02d:%02d:%02d', $hours, $diff->i, $diff->s);
                            } catch (\Exception $e) {
                                $downtime = $last;
                            }
                        }
                        $data[] = [
                            'name' => $value,
                            'router_name' => $router->identitas_router,
                            'last_disconnect' => $last ?: '-',
                            'downtime' => $downtime,
                        ];
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('status', function ($row) {
                    return
                        '<button class="btn btn-pill btn-danger btn-air-danger btn-xs" type="button" title="">Offline</button>';
                })
                ->addColumn('last_disconnect', function ($row) {
                    return $row['last_disconnect'] ?? '-';
                })
                ->addColumn('downtime', function ($row) {
                    return $row['downtime'] ?? '-';
                })
                ->rawColumns(['status'])
                ->toJson();
        }
        return view('non-active-ppps.index');
    }
}
