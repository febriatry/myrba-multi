<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LayananHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:area coverage view|package view|package category view|odc view|odp view']);
    }

    public function index(Request $request)
    {
        $tabs = [
            [
                'key' => 'area',
                'label' => 'Area Coverage',
                'permission' => 'area coverage view',
                'src' => route('area-coverages.index', ['embed' => 1]),
            ],
            [
                'key' => 'odc',
                'label' => 'ODC',
                'permission' => 'odc view',
                'src' => route('odcs.index', ['embed' => 1]),
            ],
            [
                'key' => 'odp',
                'label' => 'ODP',
                'permission' => 'odp view',
                'src' => route('odps.index', ['embed' => 1]),
            ],
            [
                'key' => 'package',
                'label' => 'Packages',
                'permission' => 'package view',
                'src' => route('packages.index', ['embed' => 1]),
            ],
            [
                'key' => 'category',
                'label' => 'Package Categories',
                'permission' => 'package category view',
                'src' => route('package-categories.index', ['embed' => 1]),
            ],
        ];

        $tab = (string) $request->query('tab', 'area');
        return view('hub.tabs', [
            'title' => 'Kelola Layanan',
            'subtitle' => 'Area, ODC/ODP, dan paket.',
            'routeName' => 'layanan-hub.index',
            'tab' => $tab,
            'tabs' => $tabs,
        ]);
    }
}

