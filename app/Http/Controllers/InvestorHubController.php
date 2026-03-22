<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InvestorHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:investor view|investor rule manage|investor payout approve|investor payout request']);
    }

    public function index(Request $request)
    {
        $tabs = [
            [
                'key' => 'dashboard',
                'label' => 'Dashboard Investor',
                'permission' => 'investor view',
                'src' => route('investor.index', ['embed' => 1]),
            ],
            [
                'key' => 'inventory',
                'label' => 'Inventory Investor',
                'permission' => 'investor view',
                'src' => route('investor-inventory.index', ['embed' => 1]),
            ],
            [
                'key' => 'admin',
                'label' => 'Dashboard Admin',
                'permission' => 'investor rule manage',
                'src' => route('investor-admin.index', ['embed' => 1]),
            ],
            [
                'key' => 'payout',
                'label' => 'Request Payout',
                'permission' => 'investor payout request',
                'src' => route('investor-payouts.index', ['embed' => 1]),
            ],
            [
                'key' => 'account',
                'label' => 'Rekening/E-Wallet',
                'permission' => 'investor payout request',
                'src' => route('investor-payout-account.index', ['embed' => 1]),
            ],
            [
                'key' => 'rules',
                'label' => 'Aturan Bagi Hasil',
                'permission' => 'investor rule manage',
                'src' => route('investor-share-rules.index', ['embed' => 1]),
            ],
            [
                'key' => 'approve',
                'label' => 'Approve Payout',
                'permission' => 'investor payout approve',
                'src' => route('investor-payout-requests.index', ['embed' => 1]),
            ],
        ];

        $tab = (string) $request->query('tab', 'dashboard');
        return view('hub.tabs', [
            'title' => 'Investor & Mitra',
            'subtitle' => 'Dashboard, aturan, dan payout.',
            'routeName' => 'investor-hub.index',
            'tab' => $tab,
            'tabs' => $tabs,
        ]);
    }
}

