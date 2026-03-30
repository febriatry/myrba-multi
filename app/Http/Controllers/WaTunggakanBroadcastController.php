<?php

namespace App\Http\Controllers;

use App\Models\AreaCoverage;
use App\Models\WaMessageStatusLog;
use App\Support\WaMessageTrigger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class WaTunggakanBroadcastController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:sendnotif view', 'permission:audit keuangan view', 'tenant.feature:whatsapp']);
    }

    public function index(Request $request)
    {
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $areaCoverages = AreaCoverage::query()
            ->when(! empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('id', $allowedAreas);
            })
            ->orderBy('kode_area')
            ->get(['id', 'kode_area', 'nama']);

        return view('wa-broadcasts.tunggakan', compact('areaCoverages'));
    }

    public function data(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $areaId = $request->query('area_id');
        $minMonths = (int) $request->query('min_months', 1);
        $onlySendable = $request->query('only_sendable', '1');
        $query = $this->pelangganMenunggakQuery(
            tenantId: $tenantId,
            allowedAreas: $allowedAreas,
            areaId: $areaId,
            minMonths: $minMonths,
            onlySendable: (string) $onlySendable,
            applyDefaultOrder: true
        );

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('total_tunggakan', fn ($row) => rupiah((float) $row->total_tunggakan))
            ->toJson();
    }

    public function send(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $allowedAreas = getAllowedAreaCoverageIdsForUser();

        $validated = $request->validate([
            'area_id' => 'nullable|integer',
            'min_months' => 'nullable|integer|min:1',
            'only_sendable' => 'nullable|in:1,0',
            'dry_run' => 'nullable|in:1,0',
            'limit' => 'nullable|integer|min:1|max:5000',
            'send_mode' => 'nullable|in:all,selected',
            'selected_ids' => 'nullable|string',
        ]);

        $waGateway = getWaGatewayActive();
        if (! $waGateway || $waGateway->is_aktif !== 'Yes') {
            return redirect()->route('wa-tunggakan.index')->with('error', 'WA Broadcast sedang nonaktif.');
        }
        $billingMode = 'owner';
        try {
            $tenant = \App\Models\Tenant::query()->find($tenantId);
            if ($tenant && strtolower((string) ($tenant->wa_provider_mode ?? 'developer')) === 'tenant') {
                $billingMode = 'tenant';
            }
        } catch (\Throwable $e) {
        }

        $areaId = $validated['area_id'] ?? null;
        $minMonths = (int) ($validated['min_months'] ?? 1);
        $onlySendable = (string) ($validated['only_sendable'] ?? '1');
        $dryRun = (string) ($validated['dry_run'] ?? '0') === '1';
        $limit = (int) ($validated['limit'] ?? 500);
        $sendMode = (string) ($validated['send_mode'] ?? 'all');
        $selectedIdsRaw = trim((string) ($validated['selected_ids'] ?? ''));
        $selectedIds = [];
        if ($selectedIdsRaw !== '') {
            $parts = array_values(array_filter(array_map('trim', explode(',', $selectedIdsRaw))));
            foreach ($parts as $p) {
                if ($p === '' || ! ctype_digit($p)) {
                    continue;
                }
                $selectedIds[] = (int) $p;
            }
            $selectedIds = array_values(array_unique(array_filter($selectedIds, fn ($v) => $v > 0)));
            if (count($selectedIds) > 5000) {
                $selectedIds = array_slice($selectedIds, 0, 5000);
            }
        }

        if ($sendMode === 'selected' && empty($selectedIds)) {
            return redirect()->route('wa-tunggakan.index')->with('error', 'Tidak ada data yang dipilih.');
        }

        $baseQuery = $this->pelangganMenunggakQuery(
            tenantId: $tenantId,
            allowedAreas: $allowedAreas,
            areaId: $areaId,
            minMonths: $minMonths,
            onlySendable: $onlySendable,
            applyDefaultOrder: true
        );

        if ($sendMode === 'selected') {
            $baseQuery->whereIn('pelanggans.id', $selectedIds);
            $rows = $baseQuery->get();
        } else {
            $rows = $baseQuery->limit($limit)->get();
        }

        if ($rows->isEmpty()) {
            return redirect()->route('wa-tunggakan.index')->with('error', 'Tidak ada pelanggan menunggak yang sesuai filter.');
        }

        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $row) {
            $noWa = trim((string) ($row->no_wa ?? ''));
            if ($noWa === '') {
                $failed++;

                continue;
            }

            $periods = [];
            $rawList = trim((string) ($row->periode_list ?? ''));
            if ($rawList !== '') {
                $periods = array_values(array_filter(array_map('trim', explode(',', $rawList))));
            }

            $message = $this->buildMessage(
                (string) ($row->nama ?? ''),
                (string) formatNoLayananTenant(($row->no_layanan ?? ''), $tenantId),
                (int) ($row->unpaid_count ?? 0),
                (float) ($row->total_tunggakan ?? 0),
                $periods
            );

            $broadcastPayload = (object) [
                'pelanggan_id' => (int) $row->pelanggan_id,
                'nama' => (string) ($row->nama ?? ''),
                'no_layanan' => (string) formatNoLayananTenant(($row->no_layanan ?? ''), $tenantId),
                'no_wa' => $noWa,
                'paket_layanan' => ! empty($row->paket_layanan) ? (int) $row->paket_layanan : null,
                'unpaid_count' => (int) ($row->unpaid_count ?? 0),
                'total_tunggakan' => (float) ($row->total_tunggakan ?? 0),
                'periode_list' => $rawList,
                'bulan_tertunggak' => $periods,
                'jumlah_bulan_tertunggak' => (int) ($row->unpaid_count ?? 0),
                'jumlah_total_tunggakan' => (float) ($row->total_tunggakan ?? 0),
                'pesan' => $message,
                'raw_message' => $message,
                'broadcast_message' => $message,
            ];

            if ($dryRun) {
                $success++;

                continue;
            }

            try {
                $parsed = sendNotifWa(
                    $waGateway->api_key ?? '',
                    $broadcastPayload,
                    WaMessageTrigger::BILLING_TOTAL,
                    $noWa
                );
                $raw = $parsed->raw ?? null;
                $rawArray = is_array($raw) ? $raw : json_decode(json_encode($raw), true);
                $messageId = $parsed->message_id ?? data_get($rawArray, 'messages.0.id');
                $status = ($parsed->status === true || $parsed->status === 'true') ? 'sent' : 'failed';
                $contextPayload = [
                    'pelanggan_id' => (int) $row->pelanggan_id,
                    'no_layanan' => (string) ($row->no_layanan ?? ''),
                    'nama' => (string) ($row->nama ?? ''),
                    'bulan_tertunggak' => $periods,
                    'jumlah_bulan_tertunggak' => (int) ($row->unpaid_count ?? 0),
                    'jumlah_total_tunggakan' => (float) ($row->total_tunggakan ?? 0),
                    'broadcast_message' => $message,
                    'gateway' => is_array($rawArray) ? $rawArray : ['raw' => $raw],
                ];
                $logType = WaMessageTrigger::BILLING_TOTAL;
                $logErrors = $status === 'sent' ? null : [['message' => $parsed->message ?? 'Unknown error']];
                $logPayload = [
                    'tunggakan_broadcast' => $contextPayload,
                ];

                if (! empty($messageId)) {
                    $existing = WaMessageStatusLog::query()
                        ->where('tenant_id', $tenantId)
                        ->where('message_id', (string) $messageId)
                        ->where('status', $status)
                        ->orderByDesc('status_at')
                        ->first();

                    if ($existing) {
                        $merged = array_merge($existing->payload ?? [], $logPayload);
                        $existing->update([
                            'recipient_id' => $existing->recipient_id ?: $noWa,
                            'type' => $existing->type ?: $logType,
                            'errors' => $logErrors,
                            'payload' => $merged,
                        ]);
                    } else {
                        WaMessageStatusLog::create([
                            'tenant_id' => $tenantId,
                            'message_id' => (string) $messageId,
                            'recipient_id' => $noWa,
                            'status' => $status,
                            'type' => $logType,
                            'status_at' => now(),
                            'errors' => $logErrors,
                            'provider' => 'ivosight',
                            'billing_mode' => $billingMode,
                            'cost_units' => 1,
                            'payload' => $logPayload,
                        ]);
                    }
                } else {
                    WaMessageStatusLog::create([
                        'tenant_id' => $tenantId,
                        'message_id' => 'tunggakan-'.uniqid(),
                        'recipient_id' => $noWa,
                        'status' => $status,
                        'type' => $logType,
                        'status_at' => now(),
                        'errors' => $logErrors,
                        'provider' => 'ivosight',
                        'billing_mode' => $billingMode,
                        'cost_units' => 1,
                        'payload' => $logPayload,
                    ]);
                }

                if ($status === 'sent') {
                    $success++;
                } else {
                    $failed++;
                    $errors[] = $noWa.': '.($parsed->message ?? 'Unknown error');
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = $noWa.': '.$e->getMessage();
                WaMessageStatusLog::create([
                    'tenant_id' => $tenantId,
                    'message_id' => 'tunggakan-'.uniqid(),
                    'recipient_id' => $noWa,
                    'status' => 'failed',
                    'type' => 'tunggakan_broadcast',
                    'status_at' => now(),
                    'errors' => [['message' => $e->getMessage()]],
                    'provider' => 'ivosight',
                    'billing_mode' => $billingMode,
                    'cost_units' => 1,
                    'payload' => [
                        'pelanggan_id' => (int) $row->pelanggan_id,
                        'no_layanan' => (string) ($row->no_layanan ?? ''),
                        'nama' => (string) ($row->nama ?? ''),
                        'exception' => $e->getMessage(),
                    ],
                ]);
                Log::error('WA tunggakan broadcast exception', [
                    'tenant_id' => $tenantId,
                    'pelanggan_id' => (int) $row->pelanggan_id,
                    'no_wa' => $noWa,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $label = $dryRun ? 'DRY RUN' : 'KIRIM';
        if ($failed > 0) {
            $errorPreview = implode(' | ', array_slice($errors, 0, 3));

            return redirect()->route('wa-tunggakan.index')->with('error', $label.' selesai. Berhasil: '.$success.', Gagal: '.$failed.'. Detail: '.$errorPreview);
        }

        return redirect()->route('wa-tunggakan.index')->with('success', $label.' berhasil. Total: '.$success);
    }

    private function buildMessage(string $nama, string $noLayanan, int $unpaidCount, float $totalTunggakan, array $periods): string
    {
        $nama = trim($nama) !== '' ? trim($nama) : 'Pelanggan';
        $noLayanan = trim($noLayanan);
        $periodText = ! empty($periods) ? implode(', ', $periods) : '-';
        $totalText = rupiah($totalTunggakan);

        $lines = [];
        $lines[] = 'Yth. '.$nama.($noLayanan !== '' ? ' (ID '.$noLayanan.')' : '');
        $lines[] = 'Kami informasikan terdapat tunggakan tagihan:';
        $lines[] = '- Jumlah bulan: '.(int) $unpaidCount;
        $lines[] = '- Periode: '.$periodText;
        $lines[] = '- Total: '.$totalText;
        $lines[] = 'Mohon segera melakukan pembayaran. Terima kasih.';

        return implode("\n", $lines);
    }

    private function pelangganMenunggakQuery(int $tenantId, array $allowedAreas, $areaId, int $minMonths, string $onlySendable, bool $applyDefaultOrder)
    {
        $sub = DB::table('tagihans')
            ->select(
                'pelanggan_id',
                DB::raw('COUNT(*) as unpaid_count'),
                DB::raw('SUM(total_bayar) as total_tunggakan'),
                DB::raw('MIN(periode) as oldest_periode'),
                DB::raw('MAX(periode) as newest_periode'),
                DB::raw('MAX(tanggal_kirim_notif_wa) as last_wa_at'),
                DB::raw("SUM(CASE WHEN is_send = 'Yes' THEN 1 ELSE 0 END) as wa_sent_count"),
                DB::raw("SUM(CASE WHEN is_send = 'No' THEN 1 ELSE 0 END) as wa_unsent_count"),
                DB::raw("GROUP_CONCAT(periode ORDER BY periode ASC SEPARATOR ',') as periode_list")
            )
            ->where('tenant_id', $tenantId)
            ->where('status_bayar', 'Belum Bayar')
            ->groupBy('pelanggan_id');

        $query = DB::query()
            ->fromSub($sub, 'u')
            ->join('pelanggans', 'u.pelanggan_id', '=', 'pelanggans.id')
            ->leftJoin('area_coverages', 'pelanggans.coverage_area', '=', 'area_coverages.id')
            ->where('pelanggans.tenant_id', $tenantId)
            ->whereIn('pelanggans.status_berlangganan', ['Aktif', 'Tunggakan'])
            ->when(! empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('pelanggans.coverage_area', $allowedAreas);
            })
            ->when(! empty($areaId) && is_numeric($areaId), function ($q) use ($areaId) {
                $q->where('pelanggans.coverage_area', (int) $areaId);
            })
            ->when($minMonths > 1, function ($q) use ($minMonths) {
                $q->where('u.unpaid_count', '>=', $minMonths);
            })
            ->when($onlySendable === '1', function ($q) {
                $q->whereNotNull('pelanggans.no_wa')
                    ->where('pelanggans.no_wa', '<>', '')
                    ->where(function ($x) {
                        $x->whereNull('pelanggans.kirim_tagihan_wa')->orWhere('pelanggans.kirim_tagihan_wa', '=', 'Yes');
                    });
            })
            ->select(
                'pelanggans.id as pelanggan_id',
                'pelanggans.no_layanan',
                'pelanggans.nama',
                'pelanggans.no_wa',
                'pelanggans.paket_layanan',
                'pelanggans.kirim_tagihan_wa',
                'pelanggans.status_berlangganan',
                'area_coverages.kode_area',
                'area_coverages.nama as area_nama',
                'u.unpaid_count',
                'u.total_tunggakan',
                'u.periode_list',
                'u.oldest_periode',
                'u.newest_periode',
                'u.last_wa_at',
                'u.wa_sent_count',
                'u.wa_unsent_count'
            );

        if ($applyDefaultOrder) {
            $query->orderByDesc('u.unpaid_count')->orderByDesc('u.total_tunggakan');
        }

        return $query;
    }
}
