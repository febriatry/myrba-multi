<?php

namespace App\Http\Controllers;

use App\Exports\SimpleArrayExport;
use App\Models\AreaCoverage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class AuditKeuanganController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:audit keuangan view')->only('index', 'summaryArea', 'pelangganTunggak', 'missingTagihan', 'waStatus');
        $this->middleware('permission:audit keuangan export')->only(
            'exportSummaryArea',
            'exportPelangganTunggak',
            'exportMissingTagihan',
            'exportWaStatus',
            'exportSummaryAreaExcel',
            'exportPelangganTunggakExcel',
            'exportMissingTagihanExcel',
            'exportWaStatusExcel',
            'exportSummaryAreaPdf',
            'exportPelangganTunggakPdf',
            'exportMissingTagihanPdf',
            'exportWaStatusPdf'
        );
    }

    public function index(Request $request)
    {
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $areaCoverages = AreaCoverage::query()
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('id', $allowedAreas);
            })
            ->orderBy('kode_area')
            ->get(['id', 'kode_area', 'nama']);

        $defaultPeriode = now()->format('Y-m');

        return view('audit-keuangan.index', compact('areaCoverages', 'defaultPeriode'));
    }

    public function summaryArea(Request $request)
    {
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $areaId = $request->query('area_id');

        $sub = DB::table('tagihans')
            ->select(
                'pelanggan_id',
                DB::raw('COUNT(*) as unpaid_count'),
                DB::raw('SUM(total_bayar) as total_tunggakan'),
                DB::raw('MIN(periode) as oldest_periode'),
                DB::raw('MAX(periode) as newest_periode'),
                DB::raw("SUM(CASE WHEN is_send = 'Yes' THEN 1 ELSE 0 END) as wa_sent_count"),
                DB::raw("SUM(CASE WHEN is_send = 'No' THEN 1 ELSE 0 END) as wa_unsent_count")
            )
            ->where('status_bayar', 'Belum Bayar')
            ->groupBy('pelanggan_id');

        $query = DB::query()
            ->fromSub($sub, 'u')
            ->join('pelanggans', 'u.pelanggan_id', '=', 'pelanggans.id')
            ->join('area_coverages', 'pelanggans.coverage_area', '=', 'area_coverages.id')
            ->whereIn('pelanggans.status_berlangganan', ['Aktif', 'Tunggakan'])
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('pelanggans.coverage_area', $allowedAreas);
            })
            ->when(!empty($areaId) && is_numeric($areaId), function ($q) use ($areaId) {
                $q->where('area_coverages.id', (int) $areaId);
            })
            ->groupBy('area_coverages.id', 'area_coverages.kode_area', 'area_coverages.nama')
            ->select(
                'area_coverages.id as area_id',
                'area_coverages.kode_area',
                'area_coverages.nama as area_nama',
                DB::raw('COUNT(*) as pelanggan_menunggak'),
                DB::raw('SUM(u.unpaid_count) as tagihan_belum_bayar'),
                DB::raw('SUM(u.total_tunggakan) as total_tunggakan'),
                DB::raw('MAX(u.unpaid_count) as max_bulan_tunggakan'),
                DB::raw("SUM(CASE WHEN u.unpaid_count = 1 THEN 1 ELSE 0 END) as tunggakan_1_bulan"),
                DB::raw("SUM(CASE WHEN u.unpaid_count = 2 THEN 1 ELSE 0 END) as tunggakan_2_bulan"),
                DB::raw("SUM(CASE WHEN u.unpaid_count >= 3 THEN 1 ELSE 0 END) as tunggakan_3_plus"),
                DB::raw('SUM(u.wa_sent_count) as wa_terkirim'),
                DB::raw('SUM(u.wa_unsent_count) as wa_belum')
            );

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('total_tunggakan', fn($row) => rupiah((float) $row->total_tunggakan))
            ->toJson();
    }

    public function pelangganTunggak(Request $request)
    {
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $areaId = $request->query('area_id');

        $sub = DB::table('tagihans')
            ->select(
                'pelanggan_id',
                DB::raw('COUNT(*) as unpaid_count'),
                DB::raw('SUM(total_bayar) as total_tunggakan'),
                DB::raw('MIN(periode) as oldest_periode'),
                DB::raw('MAX(periode) as newest_periode'),
                DB::raw('MAX(tanggal_kirim_notif_wa) as last_wa_at'),
                DB::raw("SUM(CASE WHEN is_send = 'Yes' THEN 1 ELSE 0 END) as wa_sent_count"),
                DB::raw("SUM(CASE WHEN is_send = 'No' THEN 1 ELSE 0 END) as wa_unsent_count")
            )
            ->where('status_bayar', 'Belum Bayar')
            ->groupBy('pelanggan_id');

        $query = DB::query()
            ->fromSub($sub, 'u')
            ->join('pelanggans', 'u.pelanggan_id', '=', 'pelanggans.id')
            ->leftJoin('area_coverages', 'pelanggans.coverage_area', '=', 'area_coverages.id')
            ->whereIn('pelanggans.status_berlangganan', ['Aktif', 'Tunggakan'])
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('pelanggans.coverage_area', $allowedAreas);
            })
            ->when(!empty($areaId) && is_numeric($areaId), function ($q) use ($areaId) {
                $q->where('pelanggans.coverage_area', (int) $areaId);
            })
            ->select(
                'pelanggans.id as pelanggan_id',
                'pelanggans.no_layanan',
                'pelanggans.nama',
                'pelanggans.no_wa',
                'pelanggans.kirim_tagihan_wa',
                'pelanggans.status_berlangganan',
                'area_coverages.kode_area',
                'area_coverages.nama as area_nama',
                'u.unpaid_count',
                'u.total_tunggakan',
                'u.oldest_periode',
                'u.newest_periode',
                'u.last_wa_at',
                'u.wa_sent_count',
                'u.wa_unsent_count'
            );

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('total_tunggakan', fn($row) => rupiah((float) $row->total_tunggakan))
            ->editColumn('last_wa_at', function ($row) {
                if (empty($row->last_wa_at)) {
                    return '-';
                }
                try {
                    return Carbon::parse($row->last_wa_at)->format('d/m/Y H:i');
                } catch (\Throwable $e) {
                    return (string) $row->last_wa_at;
                }
            })
            ->toJson();
    }

    public function missingTagihan(Request $request)
    {
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $areaId = $request->query('area_id');
        $periode = (string) $request->query('periode', now()->format('Y-m'));

        try {
            $endOfMonth = Carbon::createFromFormat('Y-m', $periode)->endOfMonth()->toDateString();
        } catch (\Throwable $e) {
            $periode = now()->format('Y-m');
            $endOfMonth = Carbon::createFromFormat('Y-m', $periode)->endOfMonth()->toDateString();
        }

        $query = DB::table('pelanggans')
            ->leftJoin('area_coverages', 'pelanggans.coverage_area', '=', 'area_coverages.id')
            ->leftJoin('tagihans', function ($join) use ($periode) {
                $join->on('pelanggans.id', '=', 'tagihans.pelanggan_id')
                    ->where('tagihans.periode', '=', $periode);
            })
            ->whereNull('tagihans.id')
            ->whereIn('pelanggans.status_berlangganan', ['Aktif', 'Tunggakan'])
            ->whereDate('pelanggans.tanggal_daftar', '<=', $endOfMonth)
            ->where(function ($q) {
                $q->whereNull('pelanggans.is_generate_tagihan')
                    ->orWhere('pelanggans.is_generate_tagihan', '=', 'Yes');
            })
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('pelanggans.coverage_area', $allowedAreas);
            })
            ->when(!empty($areaId) && is_numeric($areaId), function ($q) use ($areaId) {
                $q->where('pelanggans.coverage_area', (int) $areaId);
            })
            ->select(
                'pelanggans.id as pelanggan_id',
                'pelanggans.no_layanan',
                'pelanggans.nama',
                'pelanggans.tanggal_daftar',
                'pelanggans.status_berlangganan',
                'area_coverages.kode_area',
                'area_coverages.nama as area_nama',
                DB::raw("'" . $periode . "' as periode")
            );

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('tanggal_daftar', function ($row) {
                if (empty($row->tanggal_daftar)) {
                    return '-';
                }
                try {
                    return Carbon::parse($row->tanggal_daftar)->format('d/m/Y');
                } catch (\Throwable $e) {
                    return (string) $row->tanggal_daftar;
                }
            })
            ->toJson();
    }

    public function waStatus(Request $request)
    {
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $areaId = $request->query('area_id');
        $periode = (string) $request->query('periode', now()->format('Y-m'));
        $onlyUnpaid = $request->query('only_unpaid', '1');
        $onlyUnsent = $request->query('only_unsent', '0');

        $query = DB::table('tagihans')
            ->join('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->leftJoin('area_coverages', 'pelanggans.coverage_area', '=', 'area_coverages.id')
            ->where('tagihans.periode', $periode)
            ->when($onlyUnpaid === '1', function ($q) {
                $q->where('tagihans.status_bayar', 'Belum Bayar');
            })
            ->when($onlyUnsent === '1', function ($q) {
                $q->where('tagihans.is_send', 'No');
            })
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('pelanggans.coverage_area', $allowedAreas);
            })
            ->when(!empty($areaId) && is_numeric($areaId), function ($q) use ($areaId) {
                $q->where('pelanggans.coverage_area', (int) $areaId);
            })
            ->select(
                'tagihans.id as tagihan_id',
                'tagihans.no_tagihan',
                'tagihans.periode',
                'tagihans.total_bayar',
                'tagihans.status_bayar',
                'tagihans.is_send',
                'tagihans.tanggal_kirim_notif_wa',
                'pelanggans.no_layanan',
                'pelanggans.nama',
                'pelanggans.no_wa',
                'pelanggans.kirim_tagihan_wa',
                'area_coverages.kode_area',
                'area_coverages.nama as area_nama'
            );

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('total_bayar', fn($row) => rupiah((float) $row->total_bayar))
            ->editColumn('tanggal_kirim_notif_wa', function ($row) {
                if (empty($row->tanggal_kirim_notif_wa)) {
                    return '-';
                }
                try {
                    return Carbon::parse($row->tanggal_kirim_notif_wa)->format('d/m/Y H:i');
                } catch (\Throwable $e) {
                    return (string) $row->tanggal_kirim_notif_wa;
                }
            })
            ->toJson();
    }

    public function exportSummaryArea(Request $request)
    {
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $areaId = $request->query('area_id');

        $sub = DB::table('tagihans')
            ->select(
                'pelanggan_id',
                DB::raw('COUNT(*) as unpaid_count'),
                DB::raw('SUM(total_bayar) as total_tunggakan'),
                DB::raw("SUM(CASE WHEN is_send = 'Yes' THEN 1 ELSE 0 END) as wa_sent_count"),
                DB::raw("SUM(CASE WHEN is_send = 'No' THEN 1 ELSE 0 END) as wa_unsent_count")
            )
            ->where('status_bayar', 'Belum Bayar')
            ->groupBy('pelanggan_id');

        $rows = DB::query()
            ->fromSub($sub, 'u')
            ->join('pelanggans', 'u.pelanggan_id', '=', 'pelanggans.id')
            ->join('area_coverages', 'pelanggans.coverage_area', '=', 'area_coverages.id')
            ->whereIn('pelanggans.status_berlangganan', ['Aktif', 'Tunggakan'])
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('pelanggans.coverage_area', $allowedAreas);
            })
            ->when(!empty($areaId) && is_numeric($areaId), function ($q) use ($areaId) {
                $q->where('area_coverages.id', (int) $areaId);
            })
            ->groupBy('area_coverages.id', 'area_coverages.kode_area', 'area_coverages.nama')
            ->orderBy('area_coverages.kode_area')
            ->select(
                'area_coverages.kode_area',
                'area_coverages.nama as area_nama',
                DB::raw('COUNT(*) as pelanggan_menunggak'),
                DB::raw('SUM(u.unpaid_count) as tagihan_belum_bayar'),
                DB::raw('SUM(u.total_tunggakan) as total_tunggakan'),
                DB::raw('MAX(u.unpaid_count) as max_bulan_tunggakan'),
                DB::raw("SUM(CASE WHEN u.unpaid_count = 1 THEN 1 ELSE 0 END) as tunggakan_1_bulan"),
                DB::raw("SUM(CASE WHEN u.unpaid_count = 2 THEN 1 ELSE 0 END) as tunggakan_2_bulan"),
                DB::raw("SUM(CASE WHEN u.unpaid_count >= 3 THEN 1 ELSE 0 END) as tunggakan_3_plus"),
                DB::raw('SUM(u.wa_sent_count) as wa_terkirim'),
                DB::raw('SUM(u.wa_unsent_count) as wa_belum')
            )
            ->get();

        return $this->streamCsv(
            'audit_keuangan_ringkasan_area_' . now()->format('Ymd_His') . '.csv',
            [
                'kode_area',
                'nama_area',
                'pelanggan_menunggak',
                'tagihan_belum_bayar',
                'total_tunggakan',
                'max_bulan_tunggakan',
                'tunggakan_1_bulan',
                'tunggakan_2_bulan',
                'tunggakan_3_plus',
                'wa_terkirim',
                'wa_belum',
            ],
            $rows->map(function ($row) {
                return [
                    (string) ($row->kode_area ?? ''),
                    (string) ($row->area_nama ?? ''),
                    (int) ($row->pelanggan_menunggak ?? 0),
                    (int) ($row->tagihan_belum_bayar ?? 0),
                    (float) ($row->total_tunggakan ?? 0),
                    (int) ($row->max_bulan_tunggakan ?? 0),
                    (int) ($row->tunggakan_1_bulan ?? 0),
                    (int) ($row->tunggakan_2_bulan ?? 0),
                    (int) ($row->tunggakan_3_plus ?? 0),
                    (int) ($row->wa_terkirim ?? 0),
                    (int) ($row->wa_belum ?? 0),
                ];
            })->all()
        );
    }

    public function exportPelangganTunggak(Request $request)
    {
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $areaId = $request->query('area_id');

        $sub = DB::table('tagihans')
            ->select(
                'pelanggan_id',
                DB::raw('COUNT(*) as unpaid_count'),
                DB::raw('SUM(total_bayar) as total_tunggakan'),
                DB::raw('MIN(periode) as oldest_periode'),
                DB::raw('MAX(periode) as newest_periode'),
                DB::raw('MAX(tanggal_kirim_notif_wa) as last_wa_at'),
                DB::raw("SUM(CASE WHEN is_send = 'Yes' THEN 1 ELSE 0 END) as wa_sent_count"),
                DB::raw("SUM(CASE WHEN is_send = 'No' THEN 1 ELSE 0 END) as wa_unsent_count")
            )
            ->where('status_bayar', 'Belum Bayar')
            ->groupBy('pelanggan_id');

        $rows = DB::query()
            ->fromSub($sub, 'u')
            ->join('pelanggans', 'u.pelanggan_id', '=', 'pelanggans.id')
            ->leftJoin('area_coverages', 'pelanggans.coverage_area', '=', 'area_coverages.id')
            ->whereIn('pelanggans.status_berlangganan', ['Aktif', 'Tunggakan'])
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('pelanggans.coverage_area', $allowedAreas);
            })
            ->when(!empty($areaId) && is_numeric($areaId), function ($q) use ($areaId) {
                $q->where('pelanggans.coverage_area', (int) $areaId);
            })
            ->orderByDesc('u.unpaid_count')
            ->orderByDesc('u.total_tunggakan')
            ->select(
                'area_coverages.kode_area',
                'area_coverages.nama as area_nama',
                'pelanggans.no_layanan',
                'pelanggans.nama',
                'pelanggans.no_wa',
                'pelanggans.kirim_tagihan_wa',
                'pelanggans.status_berlangganan',
                'u.unpaid_count',
                'u.total_tunggakan',
                'u.oldest_periode',
                'u.newest_periode',
                'u.last_wa_at',
                'u.wa_sent_count',
                'u.wa_unsent_count'
            )
            ->get();

        return $this->streamCsv(
            'audit_keuangan_pelanggan_menunggak_' . now()->format('Ymd_His') . '.csv',
            [
                'kode_area',
                'nama_area',
                'no_layanan',
                'nama',
                'no_wa',
                'kirim_tagihan_wa',
                'status_berlangganan',
                'bulan_belum_bayar',
                'total_tunggakan',
                'periode_tertua',
                'periode_terbaru',
                'last_wa_at',
                'wa_sent_count',
                'wa_unsent_count',
            ],
            $rows->map(function ($row) {
                $lastWa = '';
                if (!empty($row->last_wa_at)) {
                    try {
                        $lastWa = Carbon::parse($row->last_wa_at)->format('Y-m-d H:i:s');
                    } catch (\Throwable $e) {
                        $lastWa = (string) $row->last_wa_at;
                    }
                }
                return [
                    (string) ($row->kode_area ?? ''),
                    (string) ($row->area_nama ?? ''),
                    (string) ($row->no_layanan ?? ''),
                    (string) ($row->nama ?? ''),
                    (string) ($row->no_wa ?? ''),
                    (string) ($row->kirim_tagihan_wa ?? ''),
                    (string) ($row->status_berlangganan ?? ''),
                    (int) ($row->unpaid_count ?? 0),
                    (float) ($row->total_tunggakan ?? 0),
                    (string) ($row->oldest_periode ?? ''),
                    (string) ($row->newest_periode ?? ''),
                    $lastWa,
                    (int) ($row->wa_sent_count ?? 0),
                    (int) ($row->wa_unsent_count ?? 0),
                ];
            })->all()
        );
    }

    public function exportMissingTagihan(Request $request)
    {
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $areaId = $request->query('area_id');
        $periode = (string) $request->query('periode', now()->format('Y-m'));

        try {
            $endOfMonth = Carbon::createFromFormat('Y-m', $periode)->endOfMonth()->toDateString();
        } catch (\Throwable $e) {
            $periode = now()->format('Y-m');
            $endOfMonth = Carbon::createFromFormat('Y-m', $periode)->endOfMonth()->toDateString();
        }

        $rows = DB::table('pelanggans')
            ->leftJoin('area_coverages', 'pelanggans.coverage_area', '=', 'area_coverages.id')
            ->leftJoin('tagihans', function ($join) use ($periode) {
                $join->on('pelanggans.id', '=', 'tagihans.pelanggan_id')
                    ->where('tagihans.periode', '=', $periode);
            })
            ->whereNull('tagihans.id')
            ->whereIn('pelanggans.status_berlangganan', ['Aktif', 'Tunggakan'])
            ->whereDate('pelanggans.tanggal_daftar', '<=', $endOfMonth)
            ->where(function ($q) {
                $q->whereNull('pelanggans.is_generate_tagihan')
                    ->orWhere('pelanggans.is_generate_tagihan', '=', 'Yes');
            })
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('pelanggans.coverage_area', $allowedAreas);
            })
            ->when(!empty($areaId) && is_numeric($areaId), function ($q) use ($areaId) {
                $q->where('pelanggans.coverage_area', (int) $areaId);
            })
            ->orderBy('pelanggans.no_layanan')
            ->select(
                'area_coverages.kode_area',
                'area_coverages.nama as area_nama',
                'pelanggans.no_layanan',
                'pelanggans.nama',
                'pelanggans.tanggal_daftar',
                'pelanggans.status_berlangganan',
                DB::raw("'" . $periode . "' as periode")
            )
            ->get();

        return $this->streamCsv(
            'audit_keuangan_tagihan_belum_dibuat_' . $periode . '_' . now()->format('Ymd_His') . '.csv',
            [
                'periode',
                'kode_area',
                'nama_area',
                'no_layanan',
                'nama',
                'tanggal_daftar',
                'status_berlangganan',
            ],
            $rows->map(function ($row) {
                $tanggalDaftar = '';
                if (!empty($row->tanggal_daftar)) {
                    try {
                        $tanggalDaftar = Carbon::parse($row->tanggal_daftar)->format('Y-m-d');
                    } catch (\Throwable $e) {
                        $tanggalDaftar = (string) $row->tanggal_daftar;
                    }
                }
                return [
                    (string) ($row->periode ?? ''),
                    (string) ($row->kode_area ?? ''),
                    (string) ($row->area_nama ?? ''),
                    (string) ($row->no_layanan ?? ''),
                    (string) ($row->nama ?? ''),
                    $tanggalDaftar,
                    (string) ($row->status_berlangganan ?? ''),
                ];
            })->all()
        );
    }

    public function exportWaStatus(Request $request)
    {
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $areaId = $request->query('area_id');
        $periode = (string) $request->query('periode', now()->format('Y-m'));
        $onlyUnpaid = $request->query('only_unpaid', '1');
        $onlyUnsent = $request->query('only_unsent', '0');

        $rows = DB::table('tagihans')
            ->join('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->leftJoin('area_coverages', 'pelanggans.coverage_area', '=', 'area_coverages.id')
            ->where('tagihans.periode', $periode)
            ->when($onlyUnpaid === '1', function ($q) {
                $q->where('tagihans.status_bayar', 'Belum Bayar');
            })
            ->when($onlyUnsent === '1', function ($q) {
                $q->where('tagihans.is_send', 'No');
            })
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('pelanggans.coverage_area', $allowedAreas);
            })
            ->when(!empty($areaId) && is_numeric($areaId), function ($q) use ($areaId) {
                $q->where('pelanggans.coverage_area', (int) $areaId);
            })
            ->orderBy('area_coverages.kode_area')
            ->orderBy('pelanggans.no_layanan')
            ->select(
                'tagihans.no_tagihan',
                'tagihans.periode',
                'tagihans.total_bayar',
                'tagihans.status_bayar',
                'tagihans.is_send',
                'tagihans.tanggal_kirim_notif_wa',
                'pelanggans.no_layanan',
                'pelanggans.nama',
                'pelanggans.no_wa',
                'pelanggans.kirim_tagihan_wa',
                'area_coverages.kode_area',
                'area_coverages.nama as area_nama'
            )
            ->get();

        return $this->streamCsv(
            'audit_keuangan_status_kirim_tagihan_' . $periode . '_' . now()->format('Ymd_His') . '.csv',
            [
                'periode',
                'kode_area',
                'nama_area',
                'no_tagihan',
                'no_layanan',
                'nama',
                'no_wa',
                'kirim_tagihan_wa',
                'total_bayar',
                'status_bayar',
                'is_send',
                'tanggal_kirim_notif_wa',
            ],
            $rows->map(function ($row) {
                $tanggalKirim = '';
                if (!empty($row->tanggal_kirim_notif_wa)) {
                    try {
                        $tanggalKirim = Carbon::parse($row->tanggal_kirim_notif_wa)->format('Y-m-d H:i:s');
                    } catch (\Throwable $e) {
                        $tanggalKirim = (string) $row->tanggal_kirim_notif_wa;
                    }
                }
                return [
                    (string) ($row->periode ?? ''),
                    (string) ($row->kode_area ?? ''),
                    (string) ($row->area_nama ?? ''),
                    (string) ($row->no_tagihan ?? ''),
                    (string) ($row->no_layanan ?? ''),
                    (string) ($row->nama ?? ''),
                    (string) ($row->no_wa ?? ''),
                    (string) ($row->kirim_tagihan_wa ?? ''),
                    (float) ($row->total_bayar ?? 0),
                    (string) ($row->status_bayar ?? ''),
                    (string) ($row->is_send ?? ''),
                    $tanggalKirim,
                ];
            })->all()
        );
    }

    public function exportSummaryAreaExcel(Request $request)
    {
        [$headings, $rows] = $this->exportDataSummaryArea($request);
        return Excel::download(new SimpleArrayExport($headings, $rows), 'audit_keuangan_ringkasan_area_' . now()->format('Ymd_His') . '.xlsx');
    }

    public function exportPelangganTunggakExcel(Request $request)
    {
        [$headings, $rows] = $this->exportDataPelangganTunggak($request);
        return Excel::download(new SimpleArrayExport($headings, $rows), 'audit_keuangan_pelanggan_menunggak_' . now()->format('Ymd_His') . '.xlsx');
    }

    public function exportMissingTagihanExcel(Request $request)
    {
        [$headings, $rows] = $this->exportDataMissingTagihan($request);
        $periode = (string) $request->query('periode', now()->format('Y-m'));
        return Excel::download(new SimpleArrayExport($headings, $rows), 'audit_keuangan_tagihan_belum_dibuat_' . $periode . '_' . now()->format('Ymd_His') . '.xlsx');
    }

    public function exportWaStatusExcel(Request $request)
    {
        [$headings, $rows] = $this->exportDataWaStatus($request);
        $periode = (string) $request->query('periode', now()->format('Y-m'));
        return Excel::download(new SimpleArrayExport($headings, $rows), 'audit_keuangan_status_kirim_tagihan_' . $periode . '_' . now()->format('Ymd_His') . '.xlsx');
    }

    public function exportSummaryAreaPdf(Request $request)
    {
        [$headers, $rows] = $this->exportDataSummaryArea($request);
        $pdf = Pdf::loadView('audit-keuangan.export-pdf', [
            'title' => 'Audit Keuangan - Ringkasan Area',
            'meta' => $this->buildExportMeta($request, includePeriode: false),
            'headers' => $headers,
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');
        return $pdf->download('audit_keuangan_ringkasan_area_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportPelangganTunggakPdf(Request $request)
    {
        [$headers, $rows] = $this->exportDataPelangganTunggak($request);
        $pdf = Pdf::loadView('audit-keuangan.export-pdf', [
            'title' => 'Audit Keuangan - Pelanggan Menunggak',
            'meta' => $this->buildExportMeta($request, includePeriode: false),
            'headers' => $headers,
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');
        return $pdf->download('audit_keuangan_pelanggan_menunggak_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportMissingTagihanPdf(Request $request)
    {
        [$headers, $rows] = $this->exportDataMissingTagihan($request);
        $periode = (string) $request->query('periode', now()->format('Y-m'));
        $pdf = Pdf::loadView('audit-keuangan.export-pdf', [
            'title' => 'Audit Keuangan - Tagihan Belum Dibuat',
            'meta' => $this->buildExportMeta($request, includePeriode: true),
            'headers' => $headers,
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');
        return $pdf->download('audit_keuangan_tagihan_belum_dibuat_' . $periode . '_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportWaStatusPdf(Request $request)
    {
        [$headers, $rows] = $this->exportDataWaStatus($request);
        $periode = (string) $request->query('periode', now()->format('Y-m'));
        $pdf = Pdf::loadView('audit-keuangan.export-pdf', [
            'title' => 'Audit Keuangan - Status Kirim Tagihan',
            'meta' => $this->buildExportMeta($request, includePeriode: true),
            'headers' => $headers,
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');
        return $pdf->download('audit_keuangan_status_kirim_tagihan_' . $periode . '_' . now()->format('Ymd_His') . '.pdf');
    }

    private function exportDataSummaryArea(Request $request): array
    {
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $areaId = $request->query('area_id');

        $sub = DB::table('tagihans')
            ->select(
                'pelanggan_id',
                DB::raw('COUNT(*) as unpaid_count'),
                DB::raw('SUM(total_bayar) as total_tunggakan'),
                DB::raw("SUM(CASE WHEN is_send = 'Yes' THEN 1 ELSE 0 END) as wa_sent_count"),
                DB::raw("SUM(CASE WHEN is_send = 'No' THEN 1 ELSE 0 END) as wa_unsent_count")
            )
            ->where('status_bayar', 'Belum Bayar')
            ->groupBy('pelanggan_id');

        $rows = DB::query()
            ->fromSub($sub, 'u')
            ->join('pelanggans', 'u.pelanggan_id', '=', 'pelanggans.id')
            ->join('area_coverages', 'pelanggans.coverage_area', '=', 'area_coverages.id')
            ->whereIn('pelanggans.status_berlangganan', ['Aktif', 'Tunggakan'])
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('pelanggans.coverage_area', $allowedAreas);
            })
            ->when(!empty($areaId) && is_numeric($areaId), function ($q) use ($areaId) {
                $q->where('area_coverages.id', (int) $areaId);
            })
            ->groupBy('area_coverages.id', 'area_coverages.kode_area', 'area_coverages.nama')
            ->orderBy('area_coverages.kode_area')
            ->select(
                'area_coverages.kode_area',
                'area_coverages.nama as area_nama',
                DB::raw('COUNT(*) as pelanggan_menunggak'),
                DB::raw('SUM(u.unpaid_count) as tagihan_belum_bayar'),
                DB::raw('SUM(u.total_tunggakan) as total_tunggakan'),
                DB::raw('MAX(u.unpaid_count) as max_bulan_tunggakan'),
                DB::raw("SUM(CASE WHEN u.unpaid_count = 1 THEN 1 ELSE 0 END) as tunggakan_1_bulan"),
                DB::raw("SUM(CASE WHEN u.unpaid_count = 2 THEN 1 ELSE 0 END) as tunggakan_2_bulan"),
                DB::raw("SUM(CASE WHEN u.unpaid_count >= 3 THEN 1 ELSE 0 END) as tunggakan_3_plus"),
                DB::raw('SUM(u.wa_sent_count) as wa_terkirim'),
                DB::raw('SUM(u.wa_unsent_count) as wa_belum')
            )
            ->get()
            ->map(function ($row) {
                return [
                    (string) ($row->kode_area ?? ''),
                    (string) ($row->area_nama ?? ''),
                    (int) ($row->pelanggan_menunggak ?? 0),
                    (int) ($row->tagihan_belum_bayar ?? 0),
                    (float) ($row->total_tunggakan ?? 0),
                    (int) ($row->max_bulan_tunggakan ?? 0),
                    (int) ($row->tunggakan_1_bulan ?? 0),
                    (int) ($row->tunggakan_2_bulan ?? 0),
                    (int) ($row->tunggakan_3_plus ?? 0),
                    (int) ($row->wa_terkirim ?? 0),
                    (int) ($row->wa_belum ?? 0),
                ];
            })
            ->all();

        return [
            [
                'kode_area',
                'nama_area',
                'pelanggan_menunggak',
                'tagihan_belum_bayar',
                'total_tunggakan',
                'max_bulan_tunggakan',
                'tunggakan_1_bulan',
                'tunggakan_2_bulan',
                'tunggakan_3_plus',
                'wa_terkirim',
                'wa_belum',
            ],
            $rows,
        ];
    }

    private function exportDataPelangganTunggak(Request $request): array
    {
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $areaId = $request->query('area_id');

        $sub = DB::table('tagihans')
            ->select(
                'pelanggan_id',
                DB::raw('COUNT(*) as unpaid_count'),
                DB::raw('SUM(total_bayar) as total_tunggakan'),
                DB::raw('MIN(periode) as oldest_periode'),
                DB::raw('MAX(periode) as newest_periode'),
                DB::raw('MAX(tanggal_kirim_notif_wa) as last_wa_at'),
                DB::raw("SUM(CASE WHEN is_send = 'Yes' THEN 1 ELSE 0 END) as wa_sent_count"),
                DB::raw("SUM(CASE WHEN is_send = 'No' THEN 1 ELSE 0 END) as wa_unsent_count")
            )
            ->where('status_bayar', 'Belum Bayar')
            ->groupBy('pelanggan_id');

        $rows = DB::query()
            ->fromSub($sub, 'u')
            ->join('pelanggans', 'u.pelanggan_id', '=', 'pelanggans.id')
            ->leftJoin('area_coverages', 'pelanggans.coverage_area', '=', 'area_coverages.id')
            ->whereIn('pelanggans.status_berlangganan', ['Aktif', 'Tunggakan'])
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('pelanggans.coverage_area', $allowedAreas);
            })
            ->when(!empty($areaId) && is_numeric($areaId), function ($q) use ($areaId) {
                $q->where('pelanggans.coverage_area', (int) $areaId);
            })
            ->orderByDesc('u.unpaid_count')
            ->orderByDesc('u.total_tunggakan')
            ->select(
                'area_coverages.kode_area',
                'area_coverages.nama as area_nama',
                'pelanggans.no_layanan',
                'pelanggans.nama',
                'pelanggans.no_wa',
                'pelanggans.kirim_tagihan_wa',
                'pelanggans.status_berlangganan',
                'u.unpaid_count',
                'u.total_tunggakan',
                'u.oldest_periode',
                'u.newest_periode',
                'u.last_wa_at',
                'u.wa_sent_count',
                'u.wa_unsent_count'
            )
            ->get()
            ->map(function ($row) {
                $lastWa = '';
                if (!empty($row->last_wa_at)) {
                    try {
                        $lastWa = Carbon::parse($row->last_wa_at)->format('Y-m-d H:i:s');
                    } catch (\Throwable $e) {
                        $lastWa = (string) $row->last_wa_at;
                    }
                }
                return [
                    (string) ($row->kode_area ?? ''),
                    (string) ($row->area_nama ?? ''),
                    (string) ($row->no_layanan ?? ''),
                    (string) ($row->nama ?? ''),
                    (string) ($row->no_wa ?? ''),
                    (string) ($row->kirim_tagihan_wa ?? ''),
                    (string) ($row->status_berlangganan ?? ''),
                    (int) ($row->unpaid_count ?? 0),
                    (float) ($row->total_tunggakan ?? 0),
                    (string) ($row->oldest_periode ?? ''),
                    (string) ($row->newest_periode ?? ''),
                    $lastWa,
                    (int) ($row->wa_sent_count ?? 0),
                    (int) ($row->wa_unsent_count ?? 0),
                ];
            })
            ->all();

        return [
            [
                'kode_area',
                'nama_area',
                'no_layanan',
                'nama',
                'no_wa',
                'kirim_tagihan_wa',
                'status_berlangganan',
                'bulan_belum_bayar',
                'total_tunggakan',
                'periode_tertua',
                'periode_terbaru',
                'last_wa_at',
                'wa_sent_count',
                'wa_unsent_count',
            ],
            $rows,
        ];
    }

    private function exportDataMissingTagihan(Request $request): array
    {
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $areaId = $request->query('area_id');
        $periode = (string) $request->query('periode', now()->format('Y-m'));

        try {
            $endOfMonth = Carbon::createFromFormat('Y-m', $periode)->endOfMonth()->toDateString();
        } catch (\Throwable $e) {
            $periode = now()->format('Y-m');
            $endOfMonth = Carbon::createFromFormat('Y-m', $periode)->endOfMonth()->toDateString();
        }

        $rows = DB::table('pelanggans')
            ->leftJoin('area_coverages', 'pelanggans.coverage_area', '=', 'area_coverages.id')
            ->leftJoin('tagihans', function ($join) use ($periode) {
                $join->on('pelanggans.id', '=', 'tagihans.pelanggan_id')
                    ->where('tagihans.periode', '=', $periode);
            })
            ->whereNull('tagihans.id')
            ->whereIn('pelanggans.status_berlangganan', ['Aktif', 'Tunggakan'])
            ->whereDate('pelanggans.tanggal_daftar', '<=', $endOfMonth)
            ->where(function ($q) {
                $q->whereNull('pelanggans.is_generate_tagihan')
                    ->orWhere('pelanggans.is_generate_tagihan', '=', 'Yes');
            })
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('pelanggans.coverage_area', $allowedAreas);
            })
            ->when(!empty($areaId) && is_numeric($areaId), function ($q) use ($areaId) {
                $q->where('pelanggans.coverage_area', (int) $areaId);
            })
            ->orderBy('pelanggans.no_layanan')
            ->select(
                'area_coverages.kode_area',
                'area_coverages.nama as area_nama',
                'pelanggans.no_layanan',
                'pelanggans.nama',
                'pelanggans.tanggal_daftar',
                'pelanggans.status_berlangganan'
            )
            ->get()
            ->map(function ($row) use ($periode) {
                $tanggalDaftar = '';
                if (!empty($row->tanggal_daftar)) {
                    try {
                        $tanggalDaftar = Carbon::parse($row->tanggal_daftar)->format('Y-m-d');
                    } catch (\Throwable $e) {
                        $tanggalDaftar = (string) $row->tanggal_daftar;
                    }
                }
                return [
                    (string) $periode,
                    (string) ($row->kode_area ?? ''),
                    (string) ($row->area_nama ?? ''),
                    (string) ($row->no_layanan ?? ''),
                    (string) ($row->nama ?? ''),
                    $tanggalDaftar,
                    (string) ($row->status_berlangganan ?? ''),
                ];
            })
            ->all();

        return [
            [
                'periode',
                'kode_area',
                'nama_area',
                'no_layanan',
                'nama',
                'tanggal_daftar',
                'status_berlangganan',
            ],
            $rows,
        ];
    }

    private function exportDataWaStatus(Request $request): array
    {
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $areaId = $request->query('area_id');
        $periode = (string) $request->query('periode', now()->format('Y-m'));
        $onlyUnpaid = $request->query('only_unpaid', '1');
        $onlyUnsent = $request->query('only_unsent', '0');

        $rows = DB::table('tagihans')
            ->join('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->leftJoin('area_coverages', 'pelanggans.coverage_area', '=', 'area_coverages.id')
            ->where('tagihans.periode', $periode)
            ->when($onlyUnpaid === '1', function ($q) {
                $q->where('tagihans.status_bayar', 'Belum Bayar');
            })
            ->when($onlyUnsent === '1', function ($q) {
                $q->where('tagihans.is_send', 'No');
            })
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('pelanggans.coverage_area', $allowedAreas);
            })
            ->when(!empty($areaId) && is_numeric($areaId), function ($q) use ($areaId) {
                $q->where('pelanggans.coverage_area', (int) $areaId);
            })
            ->orderBy('area_coverages.kode_area')
            ->orderBy('pelanggans.no_layanan')
            ->select(
                'tagihans.no_tagihan',
                'tagihans.periode',
                'tagihans.total_bayar',
                'tagihans.status_bayar',
                'tagihans.is_send',
                'tagihans.tanggal_kirim_notif_wa',
                'pelanggans.no_layanan',
                'pelanggans.nama',
                'pelanggans.no_wa',
                'pelanggans.kirim_tagihan_wa',
                'area_coverages.kode_area',
                'area_coverages.nama as area_nama'
            )
            ->get()
            ->map(function ($row) {
                $tanggalKirim = '';
                if (!empty($row->tanggal_kirim_notif_wa)) {
                    try {
                        $tanggalKirim = Carbon::parse($row->tanggal_kirim_notif_wa)->format('Y-m-d H:i:s');
                    } catch (\Throwable $e) {
                        $tanggalKirim = (string) $row->tanggal_kirim_notif_wa;
                    }
                }
                return [
                    (string) ($row->periode ?? ''),
                    (string) ($row->kode_area ?? ''),
                    (string) ($row->area_nama ?? ''),
                    (string) ($row->no_tagihan ?? ''),
                    (string) ($row->no_layanan ?? ''),
                    (string) ($row->nama ?? ''),
                    (string) ($row->no_wa ?? ''),
                    (string) ($row->kirim_tagihan_wa ?? ''),
                    (float) ($row->total_bayar ?? 0),
                    (string) ($row->status_bayar ?? ''),
                    (string) ($row->is_send ?? ''),
                    $tanggalKirim,
                ];
            })
            ->all();

        return [
            [
                'periode',
                'kode_area',
                'nama_area',
                'no_tagihan',
                'no_layanan',
                'nama',
                'no_wa',
                'kirim_tagihan_wa',
                'total_bayar',
                'status_bayar',
                'is_send',
                'tanggal_kirim_notif_wa',
            ],
            $rows,
        ];
    }

    private function buildExportMeta(Request $request, bool $includePeriode): string
    {
        $areaId = $request->query('area_id');
        $areaName = 'Semua Area';
        if (!empty($areaId) && is_numeric($areaId)) {
            $area = AreaCoverage::query()->select('kode_area', 'nama')->where('id', (int) $areaId)->first();
            if ($area) {
                $areaName = (string) $area->kode_area . ' - ' . (string) $area->nama;
            }
        }
        $meta = 'Area: ' . e($areaName) . ' | Dicetak: ' . e(now()->format('d/m/Y H:i'));
        if ($includePeriode) {
            $periode = (string) $request->query('periode', now()->format('Y-m'));
            $meta .= ' | Periode: ' . e($periode);
        }
        return $meta;
    }

    private function streamCsv(string $filename, array $headers, array $rows)
    {
        return Response::streamDownload(function () use ($headers, $rows) {
            echo "\xEF\xBB\xBF";
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
