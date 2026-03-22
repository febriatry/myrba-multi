<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WaHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:sendnotif view']);
    }

    public function index(Request $request)
    {
        $tabs = [
            [
                'key' => 'broadcast',
                'label' => 'Kirim WA Broadcast',
                'permission' => 'sendnotif view',
                'src' => route('sendnotifs.index', ['embed' => 1]),
            ],
            [
                'key' => 'tunggakan',
                'label' => 'WA Broadcast Tunggakan',
                'permission' => 'audit keuangan view',
                'src' => route('wa-tunggakan.index', ['embed' => 1]),
            ],
            [
                'key' => 'status',
                'label' => 'Monitor Status WA',
                'permission' => 'sendnotif view',
                'src' => route('wa-status-logs.index', ['embed' => 1]),
            ],
            [
                'key' => 'config',
                'label' => 'WA Config',
                'permission' => 'sendnotif view',
                'src' => route('wa-config.index', ['embed' => 1]),
            ],
        ];

        $tab = (string) $request->query('tab', 'broadcast');
        return view('hub.tabs', [
            'title' => 'WA Broadcast',
            'subtitle' => 'Kirim pesan dan monitor status.',
            'routeName' => 'wa-hub.index',
            'tab' => $tab,
            'tabs' => $tabs,
        ]);
    }
}
