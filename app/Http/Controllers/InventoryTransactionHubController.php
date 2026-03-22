<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InventoryTransactionHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:transaksi stock in view|transaksi stock out view']);
    }

    public function index(Request $request)
    {
        $tab = (string) $request->query('tab', 'in');
        return view('inventory.transaction', compact('tab'));
    }
}

