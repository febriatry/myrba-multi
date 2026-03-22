<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InventoryMasterHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:barang view|kategori barang view|unit satuan view']);
    }

    public function index(Request $request)
    {
        $tab = (string) $request->query('tab', 'barang');
        return view('inventory.master', compact('tab'));
    }
}

