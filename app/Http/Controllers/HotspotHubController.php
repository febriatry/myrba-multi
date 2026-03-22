<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HotspotHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:hotspotactive view|hotspotuser view|voucher view|hotspotprofile view']);
    }

    public function index(Request $request)
    {
        $tabs = [
            [
                'key' => 'users',
                'label' => 'Users Hotspot',
                'permission' => 'hotspotuser view',
                'src' => route('hotspotusers.index', ['embed' => 1]),
            ],
            [
                'key' => 'profiles',
                'label' => 'Profile Hotspot',
                'permission' => 'hotspotprofile view',
                'src' => route('hotspotprofiles.index', ['embed' => 1]),
            ],
            [
                'key' => 'active',
                'label' => 'Active Hotspot',
                'permission' => 'hotspotactive view',
                'src' => route('hotspotactives.index', ['embed' => 1]),
            ],
            [
                'key' => 'voucher',
                'label' => 'Voucher',
                'permission' => 'voucher view',
                'src' => route('vouchers.index', ['embed' => 1]),
            ],
        ];

        $tab = (string) $request->query('tab', 'users');
        return view('hub.tabs', [
            'title' => 'Hotspot',
            'subtitle' => 'Hotspot users, profile, active, dan voucher.',
            'routeName' => 'hotspot-hub.index',
            'tab' => $tab,
            'tabs' => $tabs,
        ]);
    }
}

