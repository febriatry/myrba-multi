<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FinanceIncomeHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:pemasukan view|category pemasukan view']);
    }

    public function index(Request $request)
    {
        $tab = (string) $request->query('tab', 'transaksi');
        return view('finance.income', compact('tab'));
    }
}

