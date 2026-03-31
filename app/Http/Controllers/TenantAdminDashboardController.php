<?php

namespace App\Http\Controllers;

class TenantAdminDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:Super Admin']);
    }

    public function index()
    {
        return redirect()->route('dashboard');
    }
}
