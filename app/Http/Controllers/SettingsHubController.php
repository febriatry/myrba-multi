<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:setting web view|user view|role & permission view|activity log view']);
    }

    public function index(Request $request)
    {
        $tabs = [
            [
                'key' => 'web',
                'label' => 'Setting Web',
                'permission' => 'setting web view',
                'src' => route('setting-webs.index', ['embed' => 1]),
            ],
            [
                'key' => 'utilities',
                'label' => 'Utilities',
                'permission' => ['user view', 'role & permission view', 'activity log view'],
                'src' => route('utilities-hub.index', ['embed' => 1]),
            ],
        ];

        $tab = (string) $request->query('tab', 'web');
        return view('hub.tabs', [
            'title' => 'Settings',
            'subtitle' => 'Pengaturan aplikasi dan manajemen akses.',
            'routeName' => 'settings-hub.index',
            'tab' => $tab,
            'tabs' => $tabs,
        ]);
    }
}

