<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FinanceReportHubController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:laporan view|audit keuangan view']);
    }

    public function index(Request $request)
    {
        $tab = (string) $request->query('tab', 'laporan');
        return view('finance.report', compact('tab'));
    }
}

