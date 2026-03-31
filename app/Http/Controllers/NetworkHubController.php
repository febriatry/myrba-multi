<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NetworkHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:log view|dhcp view|interface view|settingmikrotik view|statusrouter view|mikrotik automation view|audit pelanggan view|profile pppoe view|active ppp view|non active ppp view|secret ppp view|static view|active static view|non active static view|hotspotactive view|hotspotuser view|voucher view|hotspotprofile view|sendnotif view|olt view']);
    }

    public function index(Request $request)
    {
        $tabs = [
            [
                'key' => 'router',
                'label' => 'Router',
                'permission' => ['log view', 'dhcp view', 'interface view', 'settingmikrotik view', 'statusrouter view', 'mikrotik automation view', 'audit pelanggan view'],
                'src' => route('router-hub.index', ['embed' => 1]),
            ],
            [
                'key' => 'pppoe',
                'label' => 'PPPOE & Static',
                'permission' => ['profile pppoe view', 'active ppp view', 'non active ppp view', 'secret ppp view', 'static view', 'active static view', 'non active static view'],
                'src' => route('pppoe-hub.index', ['embed' => 1]),
            ],
            [
                'key' => 'hotspot',
                'label' => 'Hotspot',
                'permission' => ['hotspotactive view', 'hotspotuser view', 'voucher view', 'hotspotprofile view'],
                'src' => route('hotspot-hub.index', ['embed' => 1]),
            ],
            [
                'key' => 'olt',
                'label' => 'OLT',
                'permission' => 'olt view',
                'feature' => 'olt',
                'src' => route('olts.index', ['embed' => 1]),
            ],
            [
                'key' => 'wa',
                'label' => 'WA Broadcast',
                'permission' => 'sendnotif view',
                'src' => route('wa-hub.index', ['embed' => 1]),
            ],
        ];

        $tab = (string) $request->query('tab', 'router');

        return view('hub.tabs', [
            'title' => 'Network Ops',
            'subtitle' => 'Router, PPPOE, Hotspot, OLT, dan WA Broadcast.',
            'routeName' => 'network-hub.index',
            'tab' => $tab,
            'tabs' => $tabs,
        ]);
    }
}
