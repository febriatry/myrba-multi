<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class TenantAdminDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:Super Admin']);
    }

    public function index()
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $tenant = Tenant::query()->with('plan')->find($tenantId);
        $userCount = (int) DB::table('users')->where('tenant_id', $tenantId)->count();
        $pelangganCount = (int) DB::table('pelanggans')->where('tenant_id', $tenantId)->count();
        $waMonth = now()->format('Y-m');
        $waStart = $waMonth . '-01 00:00:00';
        $waEnd = date('Y-m-t', strtotime($waStart)) . ' 23:59:59';
        $waSentCount = (int) DB::table('wa_message_status_logs')
            ->where('tenant_id', $tenantId)
            ->where('status', 'sent')
            ->whereBetween('status_at', [$waStart, $waEnd])
            ->count();

        return view('tenant.dashboard', compact('tenant', 'userCount', 'pelangganCount', 'waMonth', 'waSentCount'));
    }
}
