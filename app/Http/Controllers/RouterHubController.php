<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RouterHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:log view|dhcp view|interface view|settingmikrotik view|statusrouter view|mikrotik automation view|audit pelanggan view']);
    }

    public function index(Request $request)
    {
        $tabs = [
            [
                'key' => 'daftar',
                'label' => 'Daftar Router',
                'permission' => 'settingmikrotik view',
                'src' => route('settingmikrotiks.index', ['embed' => 1]),
            ],
            [
                'key' => 'status',
                'label' => 'Status Router',
                'permission' => 'statusrouter view',
                'src' => route('statusrouters.index', ['embed' => 1]),
            ],
            [
                'key' => 'log',
                'label' => 'Log Router',
                'permission' => 'log view',
                'src' => route('logs.index', ['embed' => 1]),
            ],
            [
                'key' => 'dhcp',
                'label' => 'DHCP Leases',
                'permission' => 'dhcp view',
                'src' => route('dhcps.index', ['embed' => 1]),
            ],
            [
                'key' => 'interface',
                'label' => 'All Interface',
                'permission' => 'interface view',
                'src' => route('interfaces.index', ['embed' => 1]),
            ],
            [
                'key' => 'automation',
                'label' => 'Mikrotik Automation',
                'permission' => 'mikrotik automation view',
                'src' => route('mikrotik-automation.index', ['embed' => 1]),
            ],
            [
                'key' => 'audit',
                'label' => 'Audit Pelanggan',
                'permission' => 'audit pelanggan view',
                'src' => route('audit-pelanggan.index', ['embed' => 1]),
            ],
        ];

        $tab = (string) $request->query('tab', 'status');
        return view('hub.tabs', [
            'title' => 'Router',
            'subtitle' => 'Monitoring dan konfigurasi router.',
            'routeName' => 'router-hub.index',
            'tab' => $tab,
            'tabs' => $tabs,
        ]);
    }
}

