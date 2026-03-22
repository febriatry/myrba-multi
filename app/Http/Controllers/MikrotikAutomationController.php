<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RouterOS\Query;

class MikrotikAutomationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:mikrotik automation view'])->only('index', 'logs');
        $this->middleware(['auth', 'permission:mikrotik automation manage'])->only('saveSettings');
        $this->middleware(['auth', 'permission:mikrotik automation execute'])->only('runNow', 'manualExecute');
    }

    public function index(Request $request)
    {
        $settings = $this->getSettings();
        $areaCoverages = DB::table('area_coverages')->select('id', 'kode_area', 'nama')->orderBy('nama')->get();

        $filters = [
            'area_coverage' => $request->query('area_coverage'),
            'router' => $request->query('router'),
            'min_unpaid' => $request->query('min_unpaid', $settings['min_unpaid_invoices']),
            'overdue_only' => $request->query('overdue_only', $settings['overdue_only']),
            'include_waiting_review' => $request->query('include_waiting_review', $settings['include_waiting_review']),
        ];

        $candidates = $this->manualCandidatesQuery($filters)->limit(300)->get();
        $routers = DB::table('settingmikrotiks')->select('id', 'identitas_router')->orderBy('identitas_router')->get();

        return view('mikrotik-automation.index', compact('settings', 'areaCoverages', 'candidates', 'routers', 'filters'));
    }

    public function saveSettings(Request $request)
    {
        $request->validate([
            'is_enabled' => 'required|in:Yes,No',
            'respect_pelanggan_auto_isolir' => 'required|in:Yes,No',
            'min_unpaid_invoices' => 'required|integer|min:1|max:20',
            'overdue_only' => 'required|in:Yes,No',
            'include_waiting_review' => 'required|in:Yes,No',
            'scope_type' => 'required|in:All,AreaCoverage',
            'scope_area_ids' => 'nullable|array',
            'scope_area_ids.*' => 'integer',
            'max_execute_per_run' => 'required|integer|min:1|max:2000',
        ]);

        $payload = [
            'is_enabled' => $request->is_enabled,
            'respect_pelanggan_auto_isolir' => $request->respect_pelanggan_auto_isolir,
            'min_unpaid_invoices' => (int) $request->min_unpaid_invoices,
            'overdue_only' => $request->overdue_only,
            'include_waiting_review' => $request->include_waiting_review,
            'scope_type' => $request->scope_type,
            'scope_area_ids' => $request->scope_type === 'AreaCoverage' ? array_values(array_map('intval', (array) $request->scope_area_ids)) : null,
            'max_execute_per_run' => (int) $request->max_execute_per_run,
            'updated_at' => now(),
        ];

        $existing = DB::table('mikrotik_automation_settings')->first();
        if ($existing) {
            DB::table('mikrotik_automation_settings')->where('id', (int) $existing->id)->update($payload);
        } else {
            $payload['created_at'] = now();
            DB::table('mikrotik_automation_settings')->insert($payload);
        }

        return redirect()->route('mikrotik-automation.index')->with('success', 'Pengaturan Mikrotik Automation berhasil disimpan.');
    }

    public function runNow(Request $request)
    {
        $request->validate([
            'dry_run' => 'nullable|in:Yes,No',
        ]);
        $dryRun = ($request->dry_run ?? 'No') === 'Yes';

        $settings = $this->getSettings();
        $filters = [
            'area_coverage' => null,
            'router' => null,
            'min_unpaid' => $settings['min_unpaid_invoices'],
            'overdue_only' => $settings['overdue_only'],
            'include_waiting_review' => $settings['include_waiting_review'],
        ];

        $query = $this->manualCandidatesQuery($filters);
        if ($settings['respect_pelanggan_auto_isolir'] === 'Yes') {
            $query->where('p.auto_isolir', 'Yes');
        }
        if ($settings['scope_type'] === 'AreaCoverage' && !empty($settings['scope_area_ids'])) {
            $query->whereIn('p.coverage_area', $settings['scope_area_ids']);
        }
        $query->limit((int) $settings['max_execute_per_run']);

        $rows = $query->get();
        $processed = 0;
        $ok = 0;
        $failed = 0;
        foreach ($rows as $row) {
            $processed++;
            if ($dryRun) {
                $this->logAction('isolate', $row, 'auto_isolate_dry_run', 'skipped', null);
                continue;
            }
            try {
                $res = $this->isolatePelangganRow($row, 'auto_isolate');
                if ($res) {
                    $ok++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->logAction('isolate', $row, 'auto_isolate', 'failed', $e->getMessage());
            }
        }

        $msg = $dryRun
            ? "Simulasi selesai. target={$processed}"
            : "Eksekusi selesai. target={$processed}, ok={$ok}, gagal={$failed}";
        return redirect()->route('mikrotik-automation.index')->with('success', $msg);
    }

    public function manualExecute(Request $request)
    {
        $request->validate([
            'action' => 'required|in:isolate,unisolate',
            'pelanggan_ids' => 'required|array|min:1',
            'pelanggan_ids.*' => 'integer',
        ]);

        $action = $request->action;
        $ids = array_values(array_unique(array_map('intval', $request->pelanggan_ids)));

        $rows = DB::table('pelanggans as p')
            ->leftJoin('packages as pkg', 'p.paket_layanan', '=', 'pkg.id')
            ->leftJoin('area_coverages as ac', 'p.coverage_area', '=', 'ac.id')
            ->select(
                'p.id as pelanggan_id',
                'p.nama',
                'p.no_layanan',
                'p.coverage_area',
                'ac.nama as area_nama',
                'p.router',
                'p.mode_user',
                'p.user_pppoe',
                'p.user_static',
                'p.status_berlangganan',
                'pkg.profile'
            )
            ->whereIn('p.id', $ids)
            ->get();

        $processed = 0;
        $ok = 0;
        $failed = 0;

        foreach ($rows as $row) {
            $processed++;
            try {
                if ($action === 'isolate') {
                    $res = $this->isolatePelangganRow($row, 'manual');
                } else {
                    $res = $this->unIsolatePelangganRow($row, 'manual');
                }
                if ($res) {
                    $ok++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->logAction($action, $row, 'manual', 'failed', $e->getMessage());
            }
        }

        return redirect()->route('mikrotik-automation.index')->with('success', "Selesai. diproses={$processed}, ok={$ok}, gagal={$failed}");
    }

    public function logs(Request $request)
    {
        $logs = DB::table('mikrotik_action_logs')
            ->leftJoin('users', 'mikrotik_action_logs.performed_by', '=', 'users.id')
            ->select('mikrotik_action_logs.*', 'users.name as performed_by_name')
            ->orderByDesc('mikrotik_action_logs.id')
            ->limit(300)
            ->get();
        return view('mikrotik-automation.logs', compact('logs'));
    }

    private function getSettings(): array
    {
        $row = DB::table('mikrotik_automation_settings')->first();
        $defaults = [
            'is_enabled' => 'No',
            'respect_pelanggan_auto_isolir' => 'Yes',
            'min_unpaid_invoices' => 1,
            'overdue_only' => 'Yes',
            'include_waiting_review' => 'Yes',
            'scope_type' => 'All',
            'scope_area_ids' => [],
            'max_execute_per_run' => 200,
        ];
        if (!$row) {
            return $defaults;
        }
        $scopeIds = [];
        if (!empty($row->scope_area_ids)) {
            $decoded = json_decode($row->scope_area_ids, true);
            if (is_array($decoded)) {
                $scopeIds = array_values(array_filter(array_map('intval', $decoded)));
            }
        }
        return [
            'is_enabled' => (string) ($row->is_enabled ?? $defaults['is_enabled']),
            'respect_pelanggan_auto_isolir' => (string) ($row->respect_pelanggan_auto_isolir ?? $defaults['respect_pelanggan_auto_isolir']),
            'min_unpaid_invoices' => (int) ($row->min_unpaid_invoices ?? $defaults['min_unpaid_invoices']),
            'overdue_only' => (string) ($row->overdue_only ?? $defaults['overdue_only']),
            'include_waiting_review' => (string) ($row->include_waiting_review ?? $defaults['include_waiting_review']),
            'scope_type' => (string) ($row->scope_type ?? $defaults['scope_type']),
            'scope_area_ids' => $scopeIds,
            'max_execute_per_run' => (int) ($row->max_execute_per_run ?? $defaults['max_execute_per_run']),
        ];
    }

    private function manualCandidatesQuery(array $filters)
    {
        $minUnpaid = (int) ($filters['min_unpaid'] ?? 1);
        $overdueOnly = (string) ($filters['overdue_only'] ?? 'Yes');
        $includeWaitingReview = (string) ($filters['include_waiting_review'] ?? 'Yes');
        $unpaidStatuses = ['Belum Bayar'];
        if ($includeWaitingReview === 'Yes') {
            $unpaidStatuses[] = 'Waiting Review';
        }

        $q = DB::table('pelanggans as p')
            ->leftJoin('area_coverages as ac', 'p.coverage_area', '=', 'ac.id')
            ->select(
                'p.id as pelanggan_id',
                'p.nama',
                'p.no_layanan',
                'ac.nama as area_nama',
                'p.coverage_area',
                'p.router',
                'p.mode_user',
                'p.user_pppoe',
                'p.user_static',
                'p.status_berlangganan',
                DB::raw("(SELECT COUNT(*) FROM tagihans t WHERE t.pelanggan_id = p.id AND t.status_bayar IN ('" . implode("','", $unpaidStatuses) . "')) as unpaid_count"),
                DB::raw("(SELECT COUNT(*) FROM tagihans t2 WHERE t2.pelanggan_id = p.id AND t2.status_bayar IN ('" . implode("','", $unpaidStatuses) . "') AND DATE_ADD(t2.tanggal_create_tagihan, INTERVAL COALESCE(p.jatuh_tempo, 0) DAY) < NOW()) as overdue_count")
            )
            ->where('p.status_berlangganan', '!=', 'Menunggu')
            ->whereNotNull('p.router')
            ->where(function ($qq) {
                $qq->where(function ($q2) {
                    $q2->where('p.mode_user', 'PPOE')->whereNotNull('p.user_pppoe');
                })->orWhere(function ($q2) {
                    $q2->where('p.mode_user', '!=', 'PPOE')->whereNotNull('p.user_static');
                });
            })
            ->having('unpaid_count', '>=', $minUnpaid);

        if ($overdueOnly === 'Yes') {
            $q->having('overdue_count', '>=', 1);
        }

        if (!empty($filters['area_coverage']) && is_numeric($filters['area_coverage'])) {
            $q->where('p.coverage_area', (int) $filters['area_coverage']);
        }
        if (!empty($filters['router']) && is_numeric($filters['router'])) {
            $q->where('p.router', (int) $filters['router']);
        }

        return $q->orderByDesc('overdue_count')->orderByDesc('unpaid_count')->orderBy('p.id');
    }

    private function isolatePelangganRow($row, string $reason): bool
    {
        $client = setRouteTagihanByPelanggan((int) ($row->router ?? 0));
        if (!$client) {
            $this->logAction('isolate', $row, $reason, 'skipped', 'router tidak ditemukan');
            return false;
        }
        $identity = null;
        if (($row->mode_user ?? '') === 'PPOE') {
            $identity = (string) ($row->user_pppoe ?? '');
            if ($identity === '') {
                $this->logAction('isolate', $row, $reason, 'skipped', 'user pppoe kosong');
                return false;
            }
            $data = $client->query((new Query('/ppp/secret/print'))->where('name', $identity))->read();
            if (empty($data) || !isset($data[0]['.id'])) {
                $this->logAction('isolate', $row, $reason, 'skipped', 'secret pppoe tidak ditemukan');
                return false;
            }
            $idSecret = $data[0]['.id'];
            $existingComment = $data[0]['comment'] ?? null;
            $comment = myrbaMergeMikrotikComment($existingComment, $reason === 'manual' ? 'Isolir manual' : 'Isolir otomatis');
            $client->query((new Query('/ppp/secret/set'))->equal('.id', $idSecret)->equal('comment', $comment))->read();
            $client->query((new Query('/ppp/secret/disable'))->equal('.id', $idSecret))->read();

            $active = $client->query((new Query('/ppp/active/print'))->where('name', $identity))->read();
            if (!empty($active) && isset($active[0]['.id'])) {
                $client->query((new Query('/ppp/active/remove'))->equal('.id', $active[0]['.id']))->read();
            }
        } else {
            $identity = (string) ($row->user_static ?? '');
            if ($identity === '') {
                $this->logAction('isolate', $row, $reason, 'skipped', 'user static kosong');
                return false;
            }
            $data = $client->query((new Query('/queue/simple/print'))->where('name', $identity))->read();
            if (empty($data) || !isset($data[0]['target'])) {
                $this->logAction('isolate', $row, $reason, 'skipped', 'queue simple tidak ditemukan');
                return false;
            }
            $ip = explode('/', $data[0]['target'])[0] ?? null;
            if (!$ip) {
                $this->logAction('isolate', $row, $reason, 'skipped', 'ip target tidak ditemukan');
                return false;
            }
            $exists = $client->query((new Query('/ip/firewall/address-list/print'))->where('list', 'expired')->where('address', $ip))->read();
            if (empty($exists)) {
                $client->query((new Query('/ip/firewall/address-list/add'))->equal('list', 'expired')->equal('address', $ip))->read();
            }
        }

        DB::table('pelanggans')->where('id', (int) $row->pelanggan_id)->update([
            'status_berlangganan' => 'Non Aktif',
            'updated_at' => now(),
        ]);
        $this->logAction('isolate', $row, $reason, 'ok', null, $identity);
        return true;
    }

    private function unIsolatePelangganRow($row, string $reason): bool
    {
        $client = setRouteTagihanByPelanggan((int) ($row->router ?? 0));
        if (!$client) {
            $this->logAction('unisolate', $row, $reason, 'skipped', 'router tidak ditemukan');
            return false;
        }
        $identity = null;
        if (($row->mode_user ?? '') === 'PPOE') {
            $identity = (string) ($row->user_pppoe ?? '');
            if ($identity === '') {
                $this->logAction('unisolate', $row, $reason, 'skipped', 'user pppoe kosong');
                return false;
            }
            $data = $client->query((new Query('/ppp/secret/print'))->where('name', $identity))->read();
            if (empty($data) || !isset($data[0]['.id'])) {
                $this->logAction('unisolate', $row, $reason, 'skipped', 'secret pppoe tidak ditemukan');
                return false;
            }
            $idSecret = $data[0]['.id'];
            $existingComment = $data[0]['comment'] ?? null;
            $comment = myrbaMergeMikrotikComment($existingComment, 'Buka isolir manual');
            $client->query((new Query('/ppp/secret/set'))->equal('.id', $idSecret)->equal('comment', $comment))->read();
            $client->query((new Query('/ppp/secret/enable'))->equal('.id', $idSecret))->read();
        } else {
            $identity = (string) ($row->user_static ?? '');
            if ($identity === '') {
                $this->logAction('unisolate', $row, $reason, 'skipped', 'user static kosong');
                return false;
            }
            $data = $client->query((new Query('/queue/simple/print'))->where('name', $identity))->read();
            if (empty($data) || !isset($data[0]['target'])) {
                $this->logAction('unisolate', $row, $reason, 'skipped', 'queue simple tidak ditemukan');
                return false;
            }
            $ip = explode('/', $data[0]['target'])[0] ?? null;
            if (!$ip) {
                $this->logAction('unisolate', $row, $reason, 'skipped', 'ip target tidak ditemukan');
                return false;
            }
            $exists = $client->query((new Query('/ip/firewall/address-list/print'))->where('list', 'expired')->where('address', $ip))->read();
            if (!empty($exists) && isset($exists[0]['.id'])) {
                $client->query((new Query('/ip/firewall/address-list/remove'))->equal('.id', $exists[0]['.id']))->read();
            }
        }

        $unpaidCount = DB::table('tagihans')->where('pelanggan_id', (int) $row->pelanggan_id)->whereIn('status_bayar', ['Belum Bayar', 'Waiting Review'])->count();
        $nextStatus = $unpaidCount > 0 ? 'Tunggakan' : 'Aktif';
        DB::table('pelanggans')->where('id', (int) $row->pelanggan_id)->update([
            'status_berlangganan' => $nextStatus,
            'updated_at' => now(),
        ]);
        $this->logAction('unisolate', $row, $reason, 'ok', null, $identity);
        return true;
    }

    private function logAction(string $action, $row, string $reason, string $status, ?string $errorMessage = null, ?string $identity = null): void
    {
        DB::table('mikrotik_action_logs')->insert([
            'action' => $action,
            'pelanggan_id' => !empty($row->pelanggan_id) ? (int) $row->pelanggan_id : null,
            'router_id' => !empty($row->router) ? (int) $row->router : null,
            'mode_user' => !empty($row->mode_user) ? (string) $row->mode_user : null,
            'identity' => $identity,
            'reason' => $reason,
            'status' => $status,
            'error_message' => $errorMessage,
            'performed_by' => auth()->id(),
            'performed_via' => $reason === 'auto_isolate' || $reason === 'auto_isolate_dry_run' ? 'scheduler' : 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

