<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PelangganHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:pelanggan view|balance history view|withdraw view|withdraw create|withdraw edit|withdraw delete|withdraw approval|topup view']);
    }

    public function index(Request $request)
    {
        $tabs = [
            [
                'key' => 'daftar',
                'label' => 'Daftar Pelanggan',
                'permission' => 'pelanggan view',
                'src' => route('pelanggans.index', ['embed' => 1]),
            ],
            [
                'key' => 'request',
                'label' => 'Request',
                'permission' => 'pelanggan view',
                'src' => route('pelanggans-request.index', ['embed' => 1]),
            ],
            [
                'key' => 'balance',
                'label' => 'Historical Balance',
                'permission' => 'balance history view',
                'feature' => 'investor',
                'src' => route('balance-histories.index', ['embed' => 1]),
            ],
            [
                'key' => 'topup',
                'label' => 'Topup',
                'permission' => 'topup view',
                'feature' => 'investor',
                'src' => route('topups.index', ['embed' => 1]),
            ],
            [
                'key' => 'withdraw',
                'label' => 'Withdraw',
                'permission' => ['withdraw view', 'withdraw approval', 'withdraw create', 'withdraw edit', 'withdraw delete'],
                'feature' => 'investor',
                'src' => route('withdraws.index', ['embed' => 1]),
            ],
        ];

        $tab = (string) $request->query('tab', 'daftar');

        return view('hub.tabs', [
            'title' => 'Pelanggan',
            'subtitle' => 'Data pelanggan dan transaksi saldo.',
            'routeName' => 'pelanggan-hub.index',
            'tab' => $tab,
            'tabs' => $tabs,
        ]);
    }
}
