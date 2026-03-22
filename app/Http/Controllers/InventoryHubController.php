<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InventoryHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:unit satuan view|kategori barang view|barang view|transaksi stock in view|transaksi stock out view|laporan barang view']);
    }

    public function index(Request $request)
    {
        $tabs = [
            [
                'key' => 'master',
                'label' => 'Master',
                'permission' => ['barang view', 'kategori barang view', 'unit satuan view'],
                'src' => route('inventory-master.index', ['embed' => 1]),
            ],
            [
                'key' => 'transaksi',
                'label' => 'Transaksi',
                'permission' => ['transaksi stock in view', 'transaksi stock out view'],
                'src' => route('inventory-transactions.index', ['embed' => 1]),
            ],
            [
                'key' => 'laporan',
                'label' => 'Laporan',
                'permission' => 'laporan barang view',
                'src' => route('laporan-barang.index', ['embed' => 1]),
            ],
        ];

        $tab = (string) $request->query('tab', 'master');
        return view('hub.tabs', [
            'title' => 'Inventory',
            'subtitle' => 'Master, transaksi, dan laporan barang.',
            'routeName' => 'inventory-hub.index',
            'tab' => $tab,
            'tabs' => $tabs,
        ]);
    }
}

