<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tagihan;
use Illuminate\Support\Facades\DB;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\SettingWeb;

class LaporanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:laporan view')->only('index', 'show');
    }

    public function index(Request $request)
    {
        // Cek apakah parameter start_date dan end_date ada dalam request
        $start = $request->has('start_date') ? $request->query('start_date') : date('Y-m-01');
        $end = $request->has('end_date') ? $request->query('end_date') : date('Y-m-d');
        $allowedAreas = getAllowedAreaCoverageIdsForUser();

        // =============================================================
        $tagiahnBayar = DB::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->whereBetween('tanggal_create_tagihan', [
                $start . ' 00:00:00',
                $end . ' 23:59:59'
            ])
            ->where('status_bayar', 'Sudah Bayar')
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('pelanggans.coverage_area', $allowedAreas);
            })

            ->count();

        $nominalTagiahnBayar = DB::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->whereBetween('tanggal_create_tagihan', [
                $start . ' 00:00:00',
                $end . ' 23:59:59'
            ])
            ->where('status_bayar', 'Sudah Bayar')
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('pelanggans.coverage_area', $allowedAreas);
            })

            ->sum('tagihans.total_bayar');

        $nominalTagiahnBayarCash = DB::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->whereBetween('tanggal_create_tagihan', [
                $start . ' 00:00:00',
                $end . ' 23:59:59'
            ])
            ->where('status_bayar', 'Sudah Bayar')
            ->where('metode_bayar', 'Cash')
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('pelanggans.coverage_area', $allowedAreas);
            })

            ->sum('tagihans.total_bayar');

        $nominalTagiahnBayarPayment = DB::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->whereBetween('tanggal_create_tagihan', [
                $start . ' 00:00:00',
                $end . ' 23:59:59'
            ])
            ->where('status_bayar', 'Sudah Bayar')
            ->where('metode_bayar', 'Payment Tripay')
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('pelanggans.coverage_area', $allowedAreas);
            })

            ->sum('tagihans.total_bayar');

        $nominalTagiahnBayarTrf = DB::table('tagihans')
            ->whereBetween('tanggal_create_tagihan', [
                $start . ' 00:00:00',
                $end . ' 23:59:59'
            ])
            ->where('status_bayar', 'Sudah Bayar')
            ->where('metode_bayar', 'Transfer Bank')

            ->sum('tagihans.total_bayar');

        $tagiahnBelumBayar = DB::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->whereBetween('tanggal_create_tagihan', [
                $start . ' 00:00:00',
                $end . ' 23:59:59'
            ])
            ->where('status_bayar', 'Belum Bayar')
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('pelanggans.coverage_area', $allowedAreas);
            })

            ->count();

        $nominalTtagiahnBayar = DB::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->whereBetween('tanggal_create_tagihan', [
                $start . ' 00:00:00',
                $end . ' 23:59:59'
            ])
            ->where('status_bayar', 'Belum Bayar')
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('pelanggans.coverage_area', $allowedAreas);
            })

            ->sum('tagihans.total_bayar');
        // =============================================================

        $nominalpemasukan = DB::table('pemasukans')

            ->whereBetween('tanggal', [
                $start . ' 00:00:00',
                $end . ' 23:59:59'
            ])
            ->sum('pemasukans.nominal');

        $nominalpengeluaran = DB::table('pengeluarans')

            ->whereBetween('tanggal', [
                $start . ' 00:00:00',
                $end . ' 23:59:59'
            ])
            ->sum('pengeluarans.nominal');

        $pemasukans = DB::table('pemasukans')
            ->leftJoin('category_pemasukans', 'pemasukans.category_pemasukan_id', '=', 'category_pemasukans.id')
            ->select(
                'pemasukans.category_pemasukan_id',
                'category_pemasukans.nama_kategori_pemasukan',
                DB::raw('COUNT(pemasukans.id) as total_transaksi'),
                DB::raw('SUM(pemasukans.nominal) as total_nominal')
            )

            ->whereBetween('tanggal', [
                $start . ' 00:00:00',
                $end . ' 23:59:59'
            ])
            ->groupBy('pemasukans.category_pemasukan_id', 'category_pemasukans.nama_kategori_pemasukan')
            ->get();

        $pemasukansBySumber = DB::table('pemasukans')
            ->select(
                'pemasukans.metode_bayar',
                DB::raw('COUNT(pemasukans.id) as total_transaksi'),
                DB::raw('SUM(pemasukans.nominal) as total_nominal')
            )

            ->whereBetween('tanggal', [
                $start . ' 00:00:00',
                $end . ' 23:59:59'
            ])
            ->groupBy('pemasukans.metode_bayar')
            ->get();

        $pengeluarans = DB::table('pengeluarans')
            ->leftJoin('category_pengeluarans', 'pengeluarans.category_pengeluaran_id', '=', 'category_pengeluarans.id')
            ->select(
                'pengeluarans.category_pengeluaran_id',
                'category_pengeluarans.nama_kategori_pengeluaran',
                DB::raw('COUNT(pengeluarans.id) as total_transaksi'),
                DB::raw('SUM(pengeluarans.nominal) as total_nominal')
            )

            ->whereBetween('tanggal', [
                $start . ' 00:00:00',
                $end . ' 23:59:59'
            ])
            ->groupBy('pengeluarans.category_pengeluaran_id', 'category_pengeluarans.nama_kategori_pengeluaran')
            ->get();

        return view('laporans.index', [
            'start' => $start,
            'end' => $end,
            'nominalpemasukan' => $nominalpemasukan,
            'pemasukans' => $pemasukans,
            'pengeluarans' => $pengeluarans,
            'nominalpengeluaran' => $nominalpengeluaran,
            'pemasukansBySumber' => $pemasukansBySumber,
            'tagiahnBayar' => $tagiahnBayar,
            'nominalTagiahnBayarCash' => $nominalTagiahnBayarCash,
            'nominalTagiahnBayarPayment' => $nominalTagiahnBayarPayment,
            'nominalTagiahnBayarTrf' => $nominalTagiahnBayarTrf,
            'nominalTagiahnBayar' => $nominalTagiahnBayar,
            'tagiahnBelumBayar' => $tagiahnBelumBayar,
            'nominalTtagiahnBayar' => $nominalTtagiahnBayar,
        ]);
    }

    public function exportKas(Request $request)
    {
        $start = $request->input('start_date', date('Y-m-01'));
        $end = $request->input('end_date', date('Y-m-d'));
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $areaNames = DB::table('area_coverages')
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('id', $allowedAreas);
            })
            ->pluck('nama')->toArray();
        $wantedCategories = array_map(fn($n) => 'Pemasukan internet - ' . $n, $areaNames);

        $income = DB::table('pemasukans')
            ->leftJoin('category_pemasukans', 'pemasukans.category_pemasukan_id', '=', 'category_pemasukans.id')
            ->selectRaw('DATE(pemasukans.tanggal) as tgl, category_pemasukans.nama_kategori_pemasukan as kategori, COUNT(pemasukans.id) as jumlah, SUM(pemasukans.nominal) as total')
            ->whereBetween('pemasukans.tanggal', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->whereIn('category_pemasukans.nama_kategori_pemasukan', $wantedCategories)
            ->groupBy('tgl', 'kategori')
            ->orderBy('tgl', 'asc')
            ->get();

        $expenses = DB::table('pengeluarans')
            ->leftJoin('category_pengeluarans', 'pengeluarans.category_pengeluaran_id', '=', 'category_pengeluarans.id')
            ->select('pengeluarans.tanggal', 'pengeluarans.nominal', 'category_pengeluarans.nama_kategori_pengeluaran as kategori', 'pengeluarans.keterangan')
            ->whereBetween('pengeluarans.tanggal', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->orderBy('pengeluarans.tanggal', 'asc')
            ->get();

        $ledger = [];
        foreach ($income as $row) {
            $ledger[] = [
                'tanggal' => $row->tgl,
                'kategori' => $row->kategori,
                'keterangan' => 'Pembayaran Tagihan (' . $row->jumlah . ' transaksi)',
                'pemasukan' => (int) $row->total,
                'pengeluaran' => 0,
            ];
        }
        foreach ($expenses as $exp) {
            $ledger[] = [
                'tanggal' => substr($exp->tanggal, 0, 10),
                'kategori' => $exp->kategori,
                'keterangan' => $exp->keterangan,
                'pemasukan' => 0,
                'pengeluaran' => (int) $exp->nominal,
            ];
        }
        usort($ledger, function ($a, $b) {
            if ($a['tanggal'] === $b['tanggal']) {
                return strcmp($a['kategori'], $b['kategori']);
            }
            return strcmp($a['tanggal'], $b['tanggal']);
        });
        $running = 0;
        foreach ($ledger as $i => $row) {
            $running += $row['pemasukan'];
            $running -= $row['pengeluaran'];
            $ledger[$i]['saldo'] = $running;
        }
        $totalIncome = array_sum(array_map(fn($r) => $r['pemasukan'], $ledger));
        $totalExpenses = array_sum(array_map(fn($r) => $r['pengeluaran'], $ledger));

        $pdf = Pdf::loadView('laporans.kas_pdf', [
            'start' => $start,
            'end' => $end,
            'ledger' => $ledger,
            'totalIncome' => $totalIncome,
            'totalExpenses' => $totalExpenses,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('buku-kas-' . $start . '_to_' . $end . '.pdf');
    }


    public function getPelangganData(Request $request)
    {
        $startDate = $request->has('start_date') ? $request->query('start_date') : date('Y-m-01');
        $endDate = $request->has('end_date') ? $request->query('end_date') : date('Y-m-d');
        $viewOption = $request->input('view_option', 'daily'); // Default ke 'daily'
        $allowedAreas = getAllowedAreaCoverageIdsForUser();

        // Mulai query dasar
        $query = DB::table('pelanggans')
            ->select(DB::raw('COUNT(*) as count'));

        if (!empty($allowedAreas)) {
            $query->whereIn('coverage_area', $allowedAreas);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($startDate && $endDate) {
            $query->whereBetween('tanggal_daftar', [$startDate, $endDate]);
        }

        // Kondisi untuk setiap viewOption
        if ($viewOption == 'monthly') {
            // Query untuk pengelompokan berdasarkan bulan dan tahun
            $query->addSelect(DB::raw('YEAR(tanggal_daftar) as year'), DB::raw('MONTH(tanggal_daftar) as month'))
                ->groupBy(DB::raw('YEAR(tanggal_daftar), MONTH(tanggal_daftar)'))
                ->orderBy(DB::raw('YEAR(tanggal_daftar), MONTH(tanggal_daftar)'));
        } elseif ($viewOption == 'yearly') {
            // Query untuk pengelompokan berdasarkan tahun
            $query->addSelect(DB::raw('YEAR(tanggal_daftar) as year'))
                ->groupBy(DB::raw('YEAR(tanggal_daftar)'))
                ->orderBy(DB::raw('YEAR(tanggal_daftar)'));
        } else {
            // Default: Query untuk pengelompokan harian
            $query->addSelect(DB::raw('DATE(tanggal_daftar) as date'))
                ->groupBy(DB::raw('DATE(tanggal_daftar)'))
                ->orderBy(DB::raw('DATE(tanggal_daftar)'));
        }

        // Menjalankan query dan mengambil data
        $data = $query->get();

        // Mengembalikan data dalam format JSON untuk frontend
        return response()->json($data);
    }

    public function exportPdf(Request $request)
    {
        // Rentang tanggal dari request
        $start = $request->input('start_date', date('Y-m-01'));
        $end = $request->input('end_date', date('Y-m-d'));

        // --- Data Laporan Tagihan ---
        $tagihanLunasQuery = DB::table('tagihans')
            ->where('status_bayar', 'Sudah Bayar')
            ->whereBetween('tanggal_bayar', [$start . ' 00:00:00', $end . ' 23:59:59']);

        $totalTagihanLunas = $tagihanLunasQuery->count();
        $nominalTagihanLunas = $tagihanLunasQuery->sum('total_bayar');

        $tagihanBelumLunasQuery = DB::table('tagihans')
            ->where('status_bayar', 'Belum Bayar')
            ->whereBetween('tanggal_create_tagihan', [$start . ' 00:00:00', $end . ' 23:59:59']);

        $totalTagihanBelumLunas = $tagihanBelumLunasQuery->count();
        $nominalTagihanBelumLunas = $tagihanBelumLunasQuery->sum('total_bayar');

        // --- Detail Tagihan Lunas per Metode Bayar ---
        $detailPembayaran = DB::table('tagihans')
            ->where('status_bayar', 'Sudah Bayar')
            ->whereBetween('tanggal_bayar', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->select('metode_bayar', 'bank_account_id', DB::raw('SUM(total_bayar) as total'))
            ->groupBy('metode_bayar', 'bank_account_id')
            ->get();

        $detailTagihanPeriode = [
            'Cash' => 0,
            'Payment Tripay' => 0,
            'Transfer Bank' => [],
        ];

        foreach ($detailPembayaran as $pembayaran) {
            if ($pembayaran->metode_bayar == 'Transfer Bank' && $pembayaran->bank_account_id) {
                $bank = DB::table('bank_accounts')
                    ->join('banks', 'bank_accounts.bank_id', '=', 'banks.id')
                    ->where('bank_accounts.id', $pembayaran->bank_account_id)
                    ->select('banks.nama_bank', 'bank_accounts.nomor_rekening')
                    ->first();
                $bankKey = $bank ? ($bank->nama_bank . ' - ' . $bank->nomor_rekening) : 'Lainnya';
                $detailTagihanPeriode['Transfer Bank'][$bankKey] = ($detailTagihanPeriode['Transfer Bank'][$bankKey] ?? 0) + $pembayaran->total;
            } else {
                $detailTagihanPeriode[$pembayaran->metode_bayar] = ($detailTagihanPeriode[$pembayaran->metode_bayar] ?? 0) + $pembayaran->total;
            }
        }


        // --- Data Laporan Keuangan ---
        $pemasukanPerKategori = DB::table('pemasukans')
            ->leftJoin('category_pemasukans', 'pemasukans.category_pemasukan_id', '=', 'category_pemasukans.id')
            ->whereBetween('tanggal', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->select(
                'category_pemasukans.nama_kategori_pemasukan as kategori',
                DB::raw('COUNT(pemasukans.id) as total_transaksi'),
                DB::raw('SUM(pemasukans.nominal) as total_nominal')
            )
            ->groupBy('kategori')
            ->get();

        $pemasukanPerMetode = DB::table('pemasukans')
            ->whereBetween('tanggal', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->select(
                'metode_bayar',
                DB::raw('COUNT(id) as total_transaksi'),
                DB::raw('SUM(nominal) as total_nominal')
            )
            ->groupBy('metode_bayar')
            ->get();

        $pengeluaranPerKategori = DB::table('pengeluarans')
            ->leftJoin('category_pengeluarans', 'pengeluarans.category_pengeluaran_id', '=', 'category_pengeluarans.id')
            ->whereBetween('tanggal', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->select(
                'category_pengeluarans.nama_kategori_pengeluaran as kategori',
                DB::raw('COUNT(pengeluarans.id) as total_transaksi'),
                DB::raw('SUM(pengeluarans.nominal) as total_nominal')
            )
            ->groupBy('kategori')
            ->get();

        $totalPemasukan = $pemasukanPerKategori->sum('total_nominal');
        $totalPengeluaran = $pengeluaranPerKategori->sum('total_nominal');
        $sisaHasil = $totalPemasukan - $totalPengeluaran;

        // --- Data Tambahan untuk PDF ---
        $settingWeb = SettingWeb::first();
        $namaPembuat = auth()->user()->name;
        $tanggalCetak = Carbon::now()->translatedFormat('d F Y H:i');

        $logoUrl = null;
        if ($settingWeb && $settingWeb->logo) {
            $logoPath = storage_path('app/public/uploads/logos/' . $settingWeb->logo);
            if (file_exists($logoPath)) {
                try {
                    $logoUrl = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Gagal membaca file logo: ' . $logoPath . ' - Error: ' . $e->getMessage());
                    $logoUrl = null;
                }
            }
        }

        $data = compact(
            'start',
            'end',
            'settingWeb',
            'namaPembuat',
            'tanggalCetak',
            'logoUrl',
            'totalTagihanLunas',
            'nominalTagihanLunas',
            'totalTagihanBelumLunas',
            'nominalTagihanBelumLunas',
            'detailTagihanPeriode',
            'pemasukanPerKategori',
            'pemasukanPerMetode',
            'pengeluaranPerKategori',
            'totalPemasukan',
            'totalPengeluaran',
            'sisaHasil'
        );

        $pdf = PDF::loadView('laporans.pdf', $data);
        $pdf->setPaper('a4', 'portrait');
        return $pdf->stream('laporan-keuangan-dan-tagihan-' . $start . '-sd-' . $end . '.pdf');
    }
}
