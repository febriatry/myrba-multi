<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PppoeHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:profile pppoe view|active ppp view|non active ppp view|secret ppp view|static view|active static view|non active static view']);
    }

    public function index(Request $request)
    {
        $tabs = [
            [
                'key' => 'profile',
                'label' => 'Profile PPP',
                'permission' => 'profile pppoe view',
                'src' => route('profile-pppoes.index', ['embed' => 1]),
            ],
            [
                'key' => 'secret',
                'label' => 'Secret PPP',
                'permission' => 'secret ppp view',
                'src' => route('secret-ppps.index', ['embed' => 1]),
            ],
            [
                'key' => 'active',
                'label' => 'Active PPP',
                'permission' => 'active ppp view',
                'src' => route('active-ppps.index', ['embed' => 1]),
            ],
            [
                'key' => 'nonactive',
                'label' => 'Non Active PPP',
                'permission' => 'non active ppp view',
                'src' => route('non-active-ppps.index', ['embed' => 1]),
            ],
            [
                'key' => 'static',
                'label' => 'User Static',
                'permission' => 'static view',
                'src' => route('statics.index', ['embed' => 1]),
            ],
            [
                'key' => 'active-static',
                'label' => 'Active Static',
                'permission' => 'active static view',
                'src' => route('active-statics.index', ['embed' => 1]),
            ],
            [
                'key' => 'nonactive-static',
                'label' => 'Non Active Static',
                'permission' => 'non active static view',
                'src' => route('non-active-statics.index', ['embed' => 1]),
            ],
        ];

        $tab = (string) $request->query('tab', 'profile');
        return view('hub.tabs', [
            'title' => 'PPPOE & Static',
            'subtitle' => 'Kelola PPPoE dan static user.',
            'routeName' => 'pppoe-hub.index',
            'tab' => $tab,
            'tabs' => $tabs,
        ]);
    }
}

