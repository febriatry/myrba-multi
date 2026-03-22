<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CmsHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:tiket aduan view|banner management view|informasi management view']);
    }

    public function index(Request $request)
    {
        $tabs = [
            [
                'key' => 'tiket',
                'label' => 'Tiket Aduan',
                'permission' => 'tiket aduan view',
                'src' => route('tiket-aduans.index', ['embed' => 1]),
            ],
            [
                'key' => 'banner',
                'label' => 'Banner',
                'permission' => 'banner management view',
                'src' => route('banner-managements.index', ['embed' => 1]),
            ],
            [
                'key' => 'informasi',
                'label' => 'Informasi',
                'permission' => 'informasi management view',
                'src' => route('informasi-managements.index', ['embed' => 1]),
            ],
        ];

        $tab = (string) $request->query('tab', 'tiket');
        return view('hub.tabs', [
            'title' => 'CMS',
            'subtitle' => 'Tiket aduan, banner, dan informasi.',
            'routeName' => 'cms-hub.index',
            'tab' => $tab,
            'tabs' => $tabs,
        ]);
    }
}

