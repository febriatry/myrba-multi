<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Models\AreaCoverage;
use \RouterOS\Query;
use App\Models\Pemasukan;
use App\Models\Settingmikrotik;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use \RouterOS\Client;

class DashboardController extends Controller
{
    public function index()
    {
        $isPlatformOwner = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', \App\Models\User::class)
            ->where('model_has_roles.model_id', (int) auth()->id())
            ->where('model_has_roles.tenant_id', 0)
            ->where('roles.name', 'Platform Owner')
            ->where('roles.tenant_id', 0)
            ->exists();
        if ($isPlatformOwner) {
            return redirect()->route('platform.dashboard');
        }

        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();

        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $pelanggan = Pelanggan::select('id', 'status_berlangganan', 'tanggal_daftar', 'latitude', 'longitude', 'nama')
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('coverage_area', $allowedAreas);
            })
            ->get();

        // Hitung pelanggan baru, aktif, dan non-aktif
        $newPelanggan = Pelanggan::when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('coverage_area', $allowedAreas);
            })
            ->whereBetween('tanggal_daftar', [$currentMonthStart->toDateString(), $currentMonthEnd->toDateString()])
            ->count();
        $countPelanggan = $pelanggan->count();
        $countPelangganAktif = $pelanggan->where('status_berlangganan', 'Aktif')->count();
        $countPelangganNon = $countPelanggan - $countPelangganAktif;

        // Gunakan caching untuk data statis
        $countAreaCoverage = Cache::remember("count_area_coverage_tenant_" . $tenantId, 600, function () use ($allowedAreas) {
            return AreaCoverage::when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('id', $allowedAreas);
            })->count();
        });

        $countRouter = Cache::remember("count_router_tenant_" . $tenantId, 600, function () {
            return Settingmikrotik::count();
        });

        // Ambil data pemasukan hari ini
        $pemasukans = Pemasukan::whereBetween('tanggal', [$todayStart, $todayEnd])->get();

        // Mikrotik Queries across all routers with breakdown per router
        $routers = Settingmikrotik::select('id', 'identitas_router', 'host', 'username', 'password', 'port')->get();
        $hotspotactives = 0;
        $activePpps = 0;
        $nonactivePpps = 0;
        $staticAktif = 0;
        $staticNonAktif = 0;
        $pppActiveBreakdown = [];
        $pppNonActiveBreakdown = [];
        foreach ($routers as $r) {
            try {
                $client = new Client([
                    'host' => $r->host,
                    'user' => $r->username,
                    'pass' => $r->password,
                    'port' => (int) $r->port,
                ]);
                $identity = $client->query(new Query('/system/identity/print'))->read();
                $routerName = $identity[0]['name'] ?? $r->identitas_router ?? ('Router ' . $r->id);
                $hotspotCount = count($client->query(new Query('/ip/hotspot/active/print'))->read());
                $pppActive = $client->query(new Query('/ppp/active/print'))->read();
                $pppSecret = $client->query(new Query('/ppp/secret/print'))->read();
                $pppActiveCount = count($pppActive);
                $pppNonActiveCount = max(count($pppSecret) - $pppActiveCount, 0);
                $netUp = $client->query((new Query('/tool/netwatch/print'))->where('status', 'up'))->read();
                $netDown = $client->query((new Query('/tool/netwatch/print'))->where('status', 'down'))->read();
                $hotspotactives += $hotspotCount;
                $activePpps += $pppActiveCount;
                $nonactivePpps += $pppNonActiveCount;
                $staticAktif += count($netUp);
                $staticNonAktif += count($netDown);
                $pppActiveBreakdown[$routerName] = ($pppActiveBreakdown[$routerName] ?? 0) + $pppActiveCount;
                $pppNonActiveBreakdown[$routerName] = ($pppNonActiveBreakdown[$routerName] ?? 0) + $pppNonActiveCount;
            } catch (\Throwable $e) {
                // skip router on error
                continue;
            }
        }

        return view('dashboard', compact(
            'pelanggan',
            'countAreaCoverage',
            'countPelanggan',
            'countRouter',
            'countPelangganAktif',
            'countPelangganNon',
            'hotspotactives',
            'activePpps',
            'nonactivePpps',
            'staticAktif',
            'staticNonAktif',
            'pemasukans',
            'newPelanggan',
            'pppActiveBreakdown',
            'pppNonActiveBreakdown'
        ));
    }

    public function financeMonthly()
    {
        $isPlatformOwner = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', \App\Models\User::class)
            ->where('model_has_roles.model_id', (int) auth()->id())
            ->where('model_has_roles.tenant_id', 0)
            ->where('roles.name', 'Platform Owner')
            ->where('roles.tenant_id', 0)
            ->exists();
        if ($isPlatformOwner) {
            abort(403);
        }

        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $months = [];
        $labels = [];
        $now = Carbon::now()->startOfMonth();
        for ($i = 11; $i >= 0; $i--) {
            $m = (clone $now)->subMonths($i);
            $key = $m->format('Y-m');
            $months[] = $key;
            $labels[] = $m->format('M Y');
        }
        $start = Carbon::parse($months[0] . '-01')->startOfMonth();
        $end = Carbon::parse(end($months) . '-01')->endOfMonth();

        $incomeRows = DB::table('pemasukans')
            ->selectRaw("DATE_FORMAT(tanggal, '%Y-%m') as ym, SUM(nominal) as total")
            ->where('tenant_id', $tenantId)
            ->whereBetween('tanggal', [$start, $end])
            ->groupBy('ym')
            ->get();
        $expenseRows = DB::table('pengeluarans')
            ->selectRaw("DATE_FORMAT(tanggal, '%Y-%m') as ym, SUM(nominal) as total")
            ->where('tenant_id', $tenantId)
            ->whereBetween('tanggal', [$start, $end])
            ->groupBy('ym')
            ->get();

        $incomeMap = [];
        foreach ($incomeRows as $r) {
            $incomeMap[$r->ym] = (int) $r->total;
        }
        $expenseMap = [];
        foreach ($expenseRows as $r) {
            $expenseMap[$r->ym] = (int) $r->total;
        }

        $income = [];
        $expense = [];
        foreach ($months as $m) {
            $income[] = $incomeMap[$m] ?? 0;
            $expense[] = $expenseMap[$m] ?? 0;
        }
        return response()->json([
            'labels' => $labels,
            'income' => $income,
            'expense' => $expense,
        ]);
    }

    public function invoiceStatusMonthly()
    {
        $isPlatformOwner = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', \App\Models\User::class)
            ->where('model_has_roles.model_id', (int) auth()->id())
            ->where('model_has_roles.tenant_id', 0)
            ->where('roles.name', 'Platform Owner')
            ->where('roles.tenant_id', 0)
            ->exists();
        if ($isPlatformOwner) {
            abort(403);
        }

        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $months = [];
        $labels = [];
        $now = Carbon::now()->startOfMonth();
        for ($i = 11; $i >= 0; $i--) {
            $m = (clone $now)->subMonths($i);
            $key = $m->format('Y-m');
            $months[] = $key;
            $labels[] = $m->format('M Y');
        }
        $start = Carbon::parse($months[0] . '-01')->startOfMonth();
        $end = Carbon::parse(end($months) . '-01')->endOfMonth();
        $allowedAreas = getAllowedAreaCoverageIdsForUser();

        $rows = DB::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->selectRaw("DATE_FORMAT(tagihans.tanggal_create_tagihan, '%Y-%m') as ym, tagihans.status_bayar, COUNT(tagihans.id) as total")
            ->where('tagihans.tenant_id', $tenantId)
            ->where('pelanggans.tenant_id', $tenantId)
            ->whereBetween('tagihans.tanggal_create_tagihan', [$start, $end])
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('pelanggans.coverage_area', $allowedAreas);
            })
            ->groupBy('ym', 'tagihans.status_bayar')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[$r->ym][$r->status_bayar] = (int) $r->total;
        }
        $paid = [];
        $waiting = [];
        $unpaid = [];
        foreach ($months as $m) {
            $paid[] = $map[$m]['Sudah Bayar'] ?? 0;
            $waiting[] = $map[$m]['Waiting Review'] ?? 0;
            $unpaid[] = $map[$m]['Belum Bayar'] ?? 0;
        }
        return response()->json([
            'labels' => $labels,
            'paid' => $paid,
            'waiting' => $waiting,
            'unpaid' => $unpaid,
        ]);
    }
}
