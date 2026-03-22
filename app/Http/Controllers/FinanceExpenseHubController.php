<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FinanceExpenseHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:pengeluaran view|category pengeluaran view']);
    }

    public function index(Request $request)
    {
        $tab = (string) $request->query('tab', 'transaksi');
        return view('finance.expense', compact('tab'));
    }
}

