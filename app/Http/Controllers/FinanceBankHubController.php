<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FinanceBankHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:bank view|bank account view']);
    }

    public function index(Request $request)
    {
        $tab = (string) $request->query('tab', 'accounts');
        return view('finance.bank', compact('tab'));
    }
}

