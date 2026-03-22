<?php

namespace App\Http\Controllers;

use App\Models\Settingmikrotik;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RouterOS\Client;
use RouterOS\Query;

class AuditPelangganController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:audit pelanggan view'])->only('index');
        $this->middleware(['auth', 'permission:audit pelanggan export'])->only('exportPdf');
    }

    public function index(Request $request)
    {
        $data = $this->buildAuditData($request);
        return view('audit-pelanggan.index', $data);
    }

    public function exportPdf(Request $request)
    {
        $data = $this->buildAuditData($request);
        $meta = $this->buildMeta($request, $data['routers'] ?? collect(), $data['routerId'] ?? null);
        $pdf = Pdf::loadView('audit-pelanggan.export-pdf', [
            'title' => 'Audit Pelanggan',
            'meta' => $meta,
            'summary' => $data['summary'] ?? [],
            'anomali' => $data['anomali'] ?? [],
            'routerErrors' => $data['routerErrors'] ?? [],
        ])->setPaper('a4', 'landscape');
        return $pdf->download('audit_pelanggan_' . now()->format('Ymd_His') . '.pdf');
    }

    private function buildAuditData(Request $request): array
    {
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $routerId = $request->query('router_id');
        $routerId = is_numeric($routerId) ? (int) $routerId : null;

        $routers = Settingmikrotik::select('id', 'identitas_router', 'host', 'username', 'password', 'port')
            ->when(!empty($routerId), function ($q) use ($routerId) {
                $q->where('id', $routerId);
            })
            ->orderBy('id')
            ->get();

        $pelangganBase = DB::table('pelanggans as p')
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('p.coverage_area', $allowedAreas);
            });

        $counts = [
            'pelanggan_total' => (clone $pelangganBase)->count(),
            'pelanggan_aktif' => (clone $pelangganBase)->where('p.status_berlangganan', 'Aktif')->count(),
        ];
        $counts['pelanggan_nonaktif'] = $counts['pelanggan_total'] - $counts['pelanggan_aktif'];

        $pelangganPppoe = (clone $pelangganBase)
            ->leftJoin('area_coverages as ac', 'p.coverage_area', '=', 'ac.id')
            ->select(
                'p.id',
                'p.nama',
                'p.no_layanan',
                'p.status_berlangganan',
                'p.coverage_area',
                'ac.nama as area_nama',
                'p.router',
                'p.mode_user',
                'p.user_pppoe'
            )
            ->where('p.mode_user', 'PPOE')
            ->whereNotNull('p.router')
            ->whereNotNull('p.user_pppoe')
            ->when(!empty($routerId), function ($q) use ($routerId) {
                $q->where('p.router', $routerId);
            })
            ->orderBy('p.id')
            ->get();

        $pelangganByRouterAndUser = [];
        $duplicates = [];
        foreach ($pelangganPppoe as $p) {
            $key = ((int) $p->router) . '|' . trim((string) $p->user_pppoe);
            if (isset($pelangganByRouterAndUser[$key])) {
                $duplicates[$key] = $duplicates[$key] ?? [$pelangganByRouterAndUser[$key]];
                $duplicates[$key][] = $p;
            } else {
                $pelangganByRouterAndUser[$key] = $p;
            }
        }

        $mikrotik = [
            'ppp_secret_total' => 0,
            'ppp_active_total' => 0,
            'ppp_non_active_total' => 0,
            'router_errors' => [],
        ];

        $secretsByRouter = [];
        $activesByRouter = [];

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

                $secrets = $client->query(new Query('/ppp/secret/print'))->read();
                $actives = $client->query(new Query('/ppp/active/print'))->read();

                $secretMap = [];
                foreach ($secrets as $s) {
                    $name = trim((string) ($s['name'] ?? ''));
                    if ($name === '') {
                        continue;
                    }
                    $secretMap[$name] = [
                        'router_id' => (int) $r->id,
                        'router_name' => $routerName,
                        'name' => $name,
                        'disabled' => (string) ($s['disabled'] ?? ''),
                        'profile' => (string) ($s['profile'] ?? ''),
                        'comment' => (string) ($s['comment'] ?? ''),
                    ];
                }

                $activeMap = [];
                foreach ($actives as $a) {
                    $name = trim((string) ($a['name'] ?? ''));
                    if ($name === '') {
                        continue;
                    }
                    $activeMap[$name] = [
                        'router_id' => (int) $r->id,
                        'router_name' => $routerName,
                        'name' => $name,
                        'address' => (string) ($a['address'] ?? ''),
                        'uptime' => (string) ($a['uptime'] ?? ''),
                    ];
                }

                $secretsByRouter[(int) $r->id] = $secretMap;
                $activesByRouter[(int) $r->id] = $activeMap;

                $mikrotik['ppp_secret_total'] += count($secretMap);
                $mikrotik['ppp_active_total'] += count($activeMap);
                $mikrotik['ppp_non_active_total'] += max(count($secretMap) - count($activeMap), 0);
            } catch (\Throwable $e) {
                $mikrotik['router_errors'][] = [
                    'router_id' => (int) $r->id,
                    'router_name' => $r->identitas_router ?? ('Router ' . $r->id),
                    'error' => $e->getMessage(),
                ];
                continue;
            }
        }

        $anomali = [
            'orphan_secrets' => [],
            'missing_secrets' => [],
            'active_mismatch' => [],
            'duplicates' => [],
        ];

        foreach ($secretsByRouter as $rid => $secretMap) {
            foreach ($secretMap as $name => $secret) {
                $key = $rid . '|' . $name;
                if (!isset($pelangganByRouterAndUser[$key])) {
                    $anomali['orphan_secrets'][] = $secret;
                }
            }
        }

        foreach ($pelangganPppoe as $p) {
            $rid = (int) $p->router;
            $name = trim((string) $p->user_pppoe);
            if ($rid < 1 || $name === '') {
                continue;
            }
            if (!isset($secretsByRouter[$rid]) || !isset($secretsByRouter[$rid][$name])) {
                $anomali['missing_secrets'][] = [
                    'pelanggan_id' => (int) $p->id,
                    'nama' => $p->nama,
                    'no_layanan' => $p->no_layanan,
                    'status' => $p->status_berlangganan,
                    'router_id' => $rid,
                    'user_pppoe' => $name,
                    'area' => $p->area_nama,
                ];
            }
        }

        foreach ($activesByRouter as $rid => $activeMap) {
            foreach ($activeMap as $name => $active) {
                $key = $rid . '|' . $name;
                $p = $pelangganByRouterAndUser[$key] ?? null;
                if (!$p) {
                    $anomali['active_mismatch'][] = [
                        'type' => 'active_without_pelanggan',
                        'router_id' => $rid,
                        'router_name' => $active['router_name'],
                        'user_pppoe' => $name,
                        'address' => $active['address'],
                        'uptime' => $active['uptime'],
                    ];
                    continue;
                }
                if (($p->status_berlangganan ?? '') !== 'Aktif') {
                    $anomali['active_mismatch'][] = [
                        'type' => 'active_but_status_not_aktif',
                        'router_id' => $rid,
                        'router_name' => $active['router_name'],
                        'user_pppoe' => $name,
                        'pelanggan_id' => (int) $p->id,
                        'nama' => $p->nama,
                        'no_layanan' => $p->no_layanan,
                        'status' => $p->status_berlangganan,
                        'address' => $active['address'],
                        'uptime' => $active['uptime'],
                    ];
                }
            }
        }

        foreach ($duplicates as $key => $rows) {
            [$rid, $name] = explode('|', $key, 2);
            $anomali['duplicates'][] = [
                'router_id' => (int) $rid,
                'user_pppoe' => $name,
                'rows' => $rows,
            ];
        }

        foreach (['orphan_secrets', 'missing_secrets', 'active_mismatch', 'duplicates'] as $k) {
            $anomali[$k] = array_slice($anomali[$k], 0, 200);
        }

        $summary = [
            'router_filter_id' => $routerId,
            'ppp_secret_total' => $mikrotik['ppp_secret_total'],
            'ppp_active_total' => $mikrotik['ppp_active_total'],
            'ppp_non_active_total' => $mikrotik['ppp_non_active_total'],
            'pelanggan_total' => $counts['pelanggan_total'],
            'pelanggan_aktif' => $counts['pelanggan_aktif'],
            'pelanggan_nonaktif' => $counts['pelanggan_nonaktif'],
        ];

        return [
            'routers' => $routers,
            'routerId' => $routerId,
            'summary' => $summary,
            'anomali' => $anomali,
            'routerErrors' => $mikrotik['router_errors'],
        ];
    }

    private function buildMeta(Request $request, $routers, $routerId): string
    {
        $routerName = 'Semua Router';
        if (!empty($routerId)) {
            $row = $routers->firstWhere('id', (int) $routerId);
            if ($row) {
                $routerName = (string) ($row->identitas_router ?? ('Router ' . (int) $routerId));
            } else {
                $routerName = 'Router ' . (int) $routerId;
            }
        }
        return 'Router: ' . e($routerName) . ' | Dicetak: ' . e(now()->format('d/m/Y H:i'));
    }
}
