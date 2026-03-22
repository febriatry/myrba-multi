<?php

namespace App\Http\Controllers;

use App\Models\AreaCoverage;
use App\Http\Controllers\Api\AdminController;
use App\Models\Tagihan;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Pelanggan;
use App\Models\WaMessageStatusLog;
use PDF;
use Auth;
use \RouterOS\Query;

class TagihanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:tagihan view')->only('index', 'show');
        $this->middleware('permission:tagihan create')->only('create', 'store');
        $this->middleware('permission:tagihan edit')->only('edit', 'update');
        $this->middleware('permission:tagihan delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        if (request()->ajax()) {
            $pelanggan_id = intval($request->query('pelanggan_id'));
            $area_coverage = intval($request->query('area_coverage'));
            $metode_bayar = $request->query('metode_bayar');
            $status_bayar = $request->query('status_bayar');
            $tanggal = $request->query('tanggal'); //2023-10
            $fromMonth = $request->query('from_month'); // YYYY-MM
            $toMonth = $request->query('to_month');     // YYYY-MM
            $kirim_tagihan = $request->query('kirim_tagihan');

            $tagihans = DB::table('tagihans')
                ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
                ->leftJoin('users as creator', 'tagihans.created_by', '=', 'creator.id')
                ->leftJoin('users as reviewer', 'tagihans.reviewed_by', '=', 'reviewer.id')
                ->select(
                    'tagihans.*',
                    'pelanggans.nama',
                    'pelanggans.no_layanan',
                    'pelanggans.id as pelanggan_id',
                    'creator.name as nama_creator',
                    'reviewer.name as nama_reviewer'
                );
            $tagihans->where('tagihans.tenant_id', $tenantId)->where('pelanggans.tenant_id', $tenantId);

            $allowedAreas = getAllowedAreaCoverageIdsForUser();
            if (!empty($allowedAreas)) {
                $tagihans->whereIn('pelanggans.coverage_area', $allowedAreas);
            } else {
                $tagihans->whereRaw('1 = 0');
            }

            if (!empty($fromMonth) && !empty($toMonth)) {
                $fromStart = $fromMonth . '-01 00:00:00';
                $toStartTs = strtotime($toMonth . '-01');
                $toEnd = date('Y-m-t', $toStartTs) . ' 23:59:59';
                $tagihans->whereBetween('tagihans.tanggal_create_tagihan', [$fromStart, $toEnd]);
            } else {
                if (!empty($tanggal)) {
                    $tagihans->where('tagihans.periode', $tanggal);
                } else {
                    $tagihans->where('tagihans.periode', date('Y-m'));
                }
            }

            if (!empty($pelanggan_id) && $pelanggan_id !== 'All') {
                $tagihans->where('tagihans.pelanggan_id', $pelanggan_id);
            }

            if (!empty($area_coverage) && $area_coverage !== 'All') {
                $tagihans->where('pelanggans.coverage_area', $area_coverage);
            }

            if (!empty($metode_bayar) && $metode_bayar !== 'All') {
                $tagihans->where('tagihans.metode_bayar', $metode_bayar);
            }

            if (!empty($status_bayar) && $status_bayar !== 'All') {
                $tagihans->where('tagihans.status_bayar', $status_bayar);
            }

            if (!empty($kirim_tagihan) && $kirim_tagihan !== 'All') {
                $tagihans->where('tagihans.is_send', $kirim_tagihan);
            }

            $tagihans = $tagihans->orderBy('tagihans.id', 'DESC')->get();

            return DataTables::of($tagihans)
                ->addIndexColumn()
                ->addColumn('nominal_bayar', fn($row) => rupiah($row->nominal_bayar))
                ->addColumn('potongan_bayar', fn($row) => rupiah($row->potongan_bayar))
                ->addColumn('status_bayar_tagihan', function ($row) {
                    return match ($row->status_bayar) {
                        'Sudah Bayar' => '<button class="btn btn-success btn-sm"><i class="fa fa-check"></i> Sudah Bayar</button>',
                        'Belum Bayar' => '<button class="btn btn-danger btn-sm"><i class="fa fa-times"></i> Belum Bayar</button>',
                        default => '<button class="btn btn-secondary btn-sm"><i class="fa fa-refresh"></i> Waiting Review</button>',
                    };
                })
                ->addColumn('user_input', fn($row) => $row->nama_creator ?? '-')
                ->addColumn('user_review', fn($row) => $row->nama_reviewer ?? '-')
                ->addColumn('total_bayar', fn($row) => rupiah($row->total_bayar))
                ->addColumn('nominal_ppn', fn($row) => rupiah($row->nominal_ppn))
                ->addColumn('pelanggan', fn($row) => $row->nama)
                ->addColumn('action', 'tagihans.include.action')
                ->rawColumns(['status_bayar_tagihan', 'action'])
                ->toJson();
        }

        $thisMonth = date('Y-m');
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $pelanggans = DB::table('pelanggans')
            ->where('tenant_id', $tenantId)
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('coverage_area', $allowedAreas);
            })->get();
        $areaCoverages = AreaCoverage::when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
            $q->whereIn('id', $allowedAreas);
        })->get();

        $tanggal = $request->query('tanggal') ?? $thisMonth;
        $fromMonth = $request->query('from_month');
        $toMonth = $request->query('to_month');
        $selectedPelanggan = $request->query('pelanggan_id') !== null ? intval($request->query('pelanggan_id')) : null;
        $selectedAreaCoverage = $request->query('area_coverage') !== null ? intval($request->query('area_coverage')) : null;
        $selectedMetodeBayar = $request->query('metode_bayar') ?? null;
        $selectedStatusBayar = $request->query('status_bayar') ?? null;
        $isSend = $request->query('kirim_tagihan') ?? null;
        return view('tagihans.index', [
            'pelanggans' => $pelanggans,
            'tanggal' => $tanggal,
            'selectedPelanggan' => $selectedPelanggan,
            'selectedAreaCoverage' => $selectedAreaCoverage,
            'selectedMetodeBayar' => $selectedMetodeBayar,
            'selectedStatusBayar' => $selectedStatusBayar,
            'isSend' => $isSend,
            'thisMonth' => $tanggal,
            'fromMonth' => $fromMonth,
            'toMonth' => $toMonth,
            'areaCoverages' => $areaCoverages,
        ]);
    }

    public function summary(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $pelanggan_id = $request->query('pelanggan_id');
        $area_coverage = $request->query('area_coverage');
        $metode_bayar = $request->query('metode_bayar');
        $tanggal = $request->query('tanggal');
        $fromMonth = $request->query('from_month');
        $toMonth = $request->query('to_month');
        $kirim_tagihan = $request->query('kirim_tagihan');
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $base = DB::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id');
        $base->where('tagihans.tenant_id', $tenantId)->where('pelanggans.tenant_id', $tenantId);
        if (!empty($allowedAreas)) {
            $base->whereIn('pelanggans.coverage_area', $allowedAreas);
        } else {
            return response()->json([
                'paid_count' => 0,
                'paid_sum' => rupiah(0),
                'unpaid_count' => 0,
                'unpaid_sum' => rupiah(0),
            ]);
        }
        if (!empty($fromMonth) && !empty($toMonth)) {
            $fromStart = $fromMonth . '-01 00:00:00';
            $toStartTs = strtotime($toMonth . '-01');
            $toEnd = date('Y-m-t', $toStartTs) . ' 23:59:59';
            $base->whereBetween('tagihans.tanggal_create_tagihan', [$fromStart, $toEnd]);
        } else {
            if (!empty($tanggal)) {
                $base->where('tagihans.periode', $tanggal);
            } else {
                $base->where('tagihans.periode', date('Y-m'));
            }
        }
        if (!empty($pelanggan_id) && $pelanggan_id !== 'All') {
            $base->where('tagihans.pelanggan_id', intval($pelanggan_id));
        }
        if (!empty($area_coverage) && $area_coverage !== 'All') {
            $base->where('pelanggans.coverage_area', intval($area_coverage));
        }
        if (!empty($metode_bayar) && $metode_bayar !== 'All') {
            $base->where('tagihans.metode_bayar', $metode_bayar);
        }
        if (!empty($kirim_tagihan) && $kirim_tagihan !== 'All') {
            $base->where('tagihans.is_send', $kirim_tagihan);
        }
        $paidQuery = clone $base;
        $unpaidQuery = clone $base;
        $waitingQuery = clone $base;
        $paidCount = $paidQuery->where('tagihans.status_bayar', 'Sudah Bayar')->count();
        $paidSum = $paidQuery->sum('tagihans.total_bayar');
        $unpaidCount = $unpaidQuery->where('tagihans.status_bayar', 'Belum Bayar')->count();
        $unpaidSum = $unpaidQuery->sum('tagihans.total_bayar');
        $waitingCount = $waitingQuery->where('tagihans.status_bayar', 'Waiting Review')->count();
        $waitingSum = $waitingQuery->sum('tagihans.total_bayar');
        $status_bayar = $request->query('status_bayar');
        if (!empty($status_bayar) && $status_bayar !== 'All') {
            if ($status_bayar === 'Sudah Bayar') {
                $unpaidCount = 0;
                $unpaidSum = 0;
                $waitingCount = 0;
                $waitingSum = 0;
            } elseif ($status_bayar === 'Belum Bayar') {
                $paidCount = 0;
                $paidSum = 0;
                $waitingCount = 0;
                $waitingSum = 0;
            } elseif ($status_bayar === 'Waiting Review') {
                $paidCount = 0;
                $paidSum = 0;
                $unpaidCount = 0;
                $unpaidSum = 0;
            }
        }
        return response()->json([
            'paid_count' => $paidCount,
            'paid_sum' => rupiah($paidSum),
            'unpaid_count' => $unpaidCount,
            'unpaid_sum' => rupiah($unpaidSum),
            'waiting_count' => $waitingCount,
            'waiting_sum' => rupiah($waitingSum),
        ]);
    }

    public function create()
    {
        return view('tagihans.create');
    }

    public function store(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);

        $validator = Validator::make(
            $request->all(),
            [
                'no_tagihan' => 'required|string|max:50',
                'pelanggan_id' => 'required|exists:App\Models\Pelanggan,id',
                'nominal_bayar' => 'required|numeric',
                'potongan_bayar' => 'required|numeric',
                'total_bayar' => 'required|numeric',
                'periode' => 'required',
            ],
        );

        if ($validator->fails()) {
            return redirect()->back()->withInput($request->all())->withErrors($validator);
        }

        $pelangganOk = DB::table('pelanggans')
            ->where('id', (int) $request->pelanggan_id)
            ->where('tenant_id', $tenantId)
            ->exists();
        if (!$pelangganOk) {
            abort(404);
        }

        if ($request->ppn == 'Yes') {
            $nominal_ppn =  0.11 * ($request->nominal_bayar - $request->potongan_bayar);
        } else {
            $nominal_ppn =  0;
        }

        $tagihanId = DB::table('tagihans')->insertGetId([
            'tenant_id' => $tenantId,
            'no_tagihan' => 'INV-SSL-' . $request->no_tagihan,
            'pelanggan_id' => $request->pelanggan_id,
            'nominal_bayar' => $request->nominal_bayar,
            'potongan_bayar' => $request->potongan_bayar,
            'total_bayar' => $request->total_bayar,
            'periode' => $request->periode,
            'ppn' => $request->ppn,
            'nominal_ppn' => $nominal_ppn,
            'status_bayar' => 'Belum Bayar',
            'is_send' => 'No',
            'tanggal_create_tagihan' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        autoPayTagihanWithSaldo($request->pelanggan_id);
        
        // Auto send WA notification if active
        autoSendTagihanWa($tagihanId);

        return redirect()
            ->route('tagihans.index')
            ->with('success', __('The tagihan was created successfully.'));
    }

    public function show(Tagihan $tagihan)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $tagihan = DB::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->select('tagihans.*', 'pelanggans.nama')
            ->where('tagihans.id', '=', $tagihan->id)
            ->where('tagihans.tenant_id', $tenantId)
            ->where('pelanggans.tenant_id', $tenantId)
            ->first();

        return view('tagihans.show', compact('tagihan'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Tagihan  $tagihan
     * @return \Illuminate\Http\Response
     */
    public function edit(Tagihan $tagihan)
    {
        $tagihan->load('pelanggan:id,coverage_area');

        return view('tagihans.edit', compact('tagihan'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tagihan  $tagihan
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tagihan $tagihan)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);

        $validator = Validator::make(
            $request->all(),
            [
                'no_tagihan' => 'required|string|max:50',
                'pelanggan_id' => 'required|exists:App\Models\Pelanggan,id',
                'nominal_bayar' => 'required|numeric',
                'potongan_bayar' => 'required|numeric',
                'total_bayar' => 'required|numeric',
                'periode' => 'required',
            ],
        );

        if ($validator->fails()) {
            return redirect()->back()->withInput($request->all())->withErrors($validator);
        }

        $pelangganOk = DB::table('pelanggans')
            ->where('id', (int) $request->pelanggan_id)
            ->where('tenant_id', $tenantId)
            ->exists();
        if (!$pelangganOk) {
            abort(404);
        }

        if ($request->ppn == 'Yes') {
            $nominal_ppn =  0.11 * ($request->nominal_bayar - $request->potongan_bayar);
        } else {
            $nominal_ppn =  0;
        }
        DB::table('tagihans')
            ->where('id', $tagihan->id)
            ->where('tenant_id', $tenantId)
            ->update(
                [
                    'no_tagihan' => 'INV-SSL-' . $request->no_tagihan,
                    'pelanggan_id' => $request->pelanggan_id,
                    'nominal_bayar' => $request->nominal_bayar,
                    'potongan_bayar' => $request->potongan_bayar,
                    'ppn' => $request->ppn,
                    'nominal_ppn' =>  $nominal_ppn,
                    'total_bayar' => $request->total_bayar,
                    'periode' => $request->periode,
                    'status_bayar' => 'Belum Bayar',
                ]
            );
        autoPayTagihanWithSaldo($request->pelanggan_id);
        if ($validator->fails()) {
            return redirect()->back()->withInput($request->all())->withErrors($validator);
        }

        return redirect()
            ->route('tagihans.index')
            ->with('success', __('The tagihan was updated successfully.'));
    }

    public function destroy(Tagihan $tagihan)
    {
        try {
            $tagihan->delete();

            return redirect()
                ->route('tagihans.index')
                ->with('success', __('The tagihan was deleted successfully.'));
        } catch (\Throwable $th) {
            return redirect()
                ->route('tagihans.index')
                ->with('error', __("The tagihan can't be deleted because it's related to another table."));
        }
    }

    public function invoice($id)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $data = DB::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->leftJoin('packages', 'pelanggans.paket_layanan', '=', 'packages.id')
            ->select('tagihans.*', 'pelanggans.nama', 'pelanggans.jatuh_tempo', 'pelanggans.email as email_customer', 'pelanggans.alamat as alamat_customer', 'packages.nama_layanan', 'pelanggans.no_layanan')
            ->where('tagihans.id', '=', $id)
            ->where('tagihans.tenant_id', $tenantId)
            ->where('pelanggans.tenant_id', $tenantId)
            ->first();
        return view('tagihans.print', compact('data'));
    }

    public function invoiceSigned($id)
    {
        return $this->invoice($id);
    }

    public function invoiceEscpos(Request $request, $id)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $data = DB::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->leftJoin('packages', 'pelanggans.paket_layanan', '=', 'packages.id')
            ->select('tagihans.*', 'pelanggans.nama', 'pelanggans.jatuh_tempo', 'pelanggans.email as email_customer', 'pelanggans.alamat as alamat_customer', 'packages.nama_layanan', 'pelanggans.no_layanan')
            ->where('tagihans.id', '=', $id)
            ->where('tagihans.tenant_id', $tenantId)
            ->where('pelanggans.tenant_id', $tenantId)
            ->first();
        $setting = DB::table('setting_web')->first();
        $tgl = date('Y-m-d', strtotime($data->tanggal_create_tagihan));
        $jatuhTempo = date('Y-m-d', strtotime('+7 days', strtotime($tgl)));
        $subtotal = $data->nominal_bayar - $data->potongan_bayar;
        $max = 32;
        $wrap = function ($s) use ($max) { return implode("\n", str_split($s, $max)); };
        $lines = [
            $wrap('Myrba Billing System'),
            $wrap($setting->nama_perusahaan),
            $wrap('Invoice ' . $data->no_tagihan),
            $wrap('Tanggal: ' . $tgl),
            $wrap('Jatuh Tempo: ' . $jatuhTempo),
            '----------',
            $wrap('Kepada: ' . $data->nama),
            $wrap('No Layanan: ' . $data->no_layanan),
            $wrap('Alamat: ' . ($data->alamat_customer ?? '-')),
            '----------',
            $wrap('Internet ' . $setting->nama_perusahaan . ' - ' . $data->nama_layanan),
            $wrap('Qty: 1'),
            $wrap('Harga: ' . rupiah($subtotal)),
            $wrap('Total: ' . rupiah($subtotal)),
            $wrap('Subtotal: ' . rupiah($subtotal)),
        ];
        if ($data->ppn == 'Yes') {
            $lines[] = $wrap('PPN 11%: ' . rupiah($data->nominal_ppn));
        }
        $lines[] = $wrap('Grand Total: ' . rupiah($data->total_bayar));
        $lines[] = '----------';
        $lines[] = $wrap('Selalu cek tagihan Anda di myrba.net');
        $lines[] = $wrap('Masukkan nomor ID Anda');
        $lines[] = $wrap('Terima kasih');
        if ($request->query('format') === 'plain') {
            return response(implode("\n", $lines), 200)->header('Content-Type', 'text/plain');
        } else {
            return response()->json([
                'status' => true,
                'format' => 'escpos',
                'width_mm' => 57,
                'max_chars_per_line' => $max,
                'lines' => $lines,
            ]);
        }
    }

    public function sendTagihanWa($tagihan_id)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $getWaGatewayActive = getWaGatewayActive();
        $tagihans = DB::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->select('tagihans.*', 'pelanggans.nama', 'pelanggans.no_wa', 'pelanggans.no_layanan', 'pelanggans.jatuh_tempo')
            ->where('tagihans.id', '=', $tagihan_id)
            ->where('tagihans.tenant_id', $tenantId)
            ->where('pelanggans.tenant_id', $tenantId)
            ->first();
        if (!$tagihans) {
            return redirect()
                ->route('tagihans.index')
                ->with('error', __('Tagihan tidak ditemukan.'));
        }
        if (empty($tagihans->no_wa)) {
            return redirect()
                ->route('tagihans.index')
                ->with('error', __('Nomor WhatsApp pelanggan tidak tersedia.'));
        }
        if ($getWaGatewayActive->is_aktif == 'Yes') {
            try {
                $isPaid = in_array((string) ($tagihans->status_bayar ?? ''), ['Sudah Bayar', 'PAID', 'Paid'], true);
                $triggerType = $isPaid ? 'payment_receipt' : 'billing_reminder';
                $statusLogType = $isPaid ? 'bayar' : 'tagihan';
                $successMessage = $isPaid ? __('Kirim bukti pembayaran berhasil') : __('Kirim notifikasi tagihan berhasil');
                $failedPrefix = $isPaid ? __('Kirim bukti pembayaran gagal ') : __('Kirim notifikasi tagihan gagal ');
                $res = sendNotifWa(
                    $getWaGatewayActive->api_key,
                    $tagihans,
                    $triggerType,
                    $tagihans->no_wa
                );
                if ($res->status == true || $res->status == 'true') {
                    // update
                    DB::table('tagihans')
                        ->where('tagihans.id', $tagihan_id)
                        ->update(['is_send' => 'Yes', 'tanggal_kirim_notif_wa' => now()]);
                    WaMessageStatusLog::create([
                        'tenant_id' => $tenantId,
                        'message_id' => $res->message_id ?? ('tagihan-' . $tagihan_id . '-' . uniqid()),
                        'recipient_id' => (string) $tagihans->no_wa,
                        'status' => 'sent',
                        'type' => $statusLogType,
                        'status_at' => now(),
                        'errors' => null,
                        'payload' => isset($res->raw) ? (array) $res->raw : null,
                    ]);
                    return redirect()
                        ->route('tagihans.index')
                        ->with('success', $successMessage);
                } else {
                    WaMessageStatusLog::create([
                        'tenant_id' => $tenantId,
                        'message_id' => 'tagihan-' . $tagihan_id . '-' . uniqid(),
                        'recipient_id' => (string) $tagihans->no_wa,
                        'status' => 'failed',
                        'type' => $statusLogType,
                        'status_at' => now(),
                        'errors' => [['message' => $res->message ?? 'Unknown error']],
                        'payload' => isset($res->raw) ? (array) $res->raw : null,
                    ]);
                    return redirect()
                        ->route('tagihans.index')
                        ->with('error', $failedPrefix . $res->message);
                }
            } catch (\Throwable $e) {
                Log::error('Send tagihan WA exception', [
                    'tagihan_id' => $tagihan_id,
                    'no_wa' => $tagihans->no_wa,
                    'error' => $e->getMessage(),
                ]);
                WaMessageStatusLog::create([
                    'tenant_id' => $tenantId,
                    'message_id' => 'tagihan-' . $tagihan_id . '-' . uniqid(),
                    'recipient_id' => (string) $tagihans->no_wa,
                    'status' => 'failed',
                    'type' => in_array((string) ($tagihans->status_bayar ?? ''), ['Sudah Bayar', 'PAID', 'Paid'], true) ? 'bayar' : 'tagihan',
                    'status_at' => now(),
                    'errors' => [['message' => $e->getMessage()]],
                    'payload' => ['exception' => $e->getMessage()],
                ]);
                return redirect()
                    ->route('tagihans.index')
                    ->with('error', __('Kirim notifikasi tagihan gagal: ') . $e->getMessage());
            }
        } else {
            return redirect()
                ->route('tagihans.index')
                ->with('error', __('Setting is active wa Off'));
        }
    }

    public function sendInvoice($tagihan_id)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $getWaGatewayActive = getWaGatewayActive();
        $tagihans = DB::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->select('tagihans.*', 'pelanggans.nama', 'pelanggans.no_wa', 'pelanggans.no_layanan', 'pelanggans.jatuh_tempo')
            ->where('tagihans.id', '=', $tagihan_id)
            ->where('tagihans.tenant_id', $tenantId)
            ->where('pelanggans.tenant_id', $tenantId)
            ->first();

        if ($getWaGatewayActive->is_aktif == 'Yes') {
            try {
                $res = sendNotifWa(
                    $getWaGatewayActive->api_key,
                    $tagihans,
                    'invoice_link',
                    $tagihans->no_wa
                );

                if ($res->status == true || $res->status == 'true') {
                    return back()->with('success', __('Kirim invoice berhasil'));
                } else {
                    return back()->with('error', __('Kirim invoice gagal ') . $res->message);
                }
            } catch (\Exception $e) {
                return back()->with('error', __('Caught exception: ') . $e->getMessage());
            }
        } else {
            return back()->with('error', __('Setting is active wa Off'));
        }
    }

    public function sendWa(Request $request)
    {
        $ids = $request->input('ids', []);
        $getWaGatewayActive = getWaGatewayActive();

        // Check if the WhatsApp gateway is active
        if ($getWaGatewayActive->is_aktif !== 'Yes') {
            return response()->json(['message' => 'Gateway WA tidak aktif.'], 400);
        }

        $responses = [];
        $errors = [];
        $errorMessages = [];

        foreach ($ids as $id) {
            try {
                // Fetch the tagihan and related pelanggan
                $tagihan = DB::table('tagihans')
                    ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
                    ->select('tagihans.*', 'pelanggans.nama', 'pelanggans.no_wa', 'pelanggans.no_layanan', 'pelanggans.jatuh_tempo')
                    ->where('tagihans.id', $id)
                    ->first();

                if ($tagihan) {
                    if (empty($tagihan->no_wa)) {
                        $errors[] = 'No WA kosong';
                        $errorMessages['No WA kosong'] = "No WA kosong pada tagihan ID {$id}";
                        continue;
                    }
                    $isPaid = in_array((string) ($tagihan->status_bayar ?? ''), ['Sudah Bayar', 'PAID', 'Paid'], true);
                    $triggerType = $isPaid ? 'payment_receipt' : 'billing_reminder';
                    $statusLogType = $isPaid ? 'bayar' : 'tagihan';
                    // Send notification
                    $response = sendNotifWa(
                        $getWaGatewayActive->api_key,
                        $tagihan,
                        $triggerType,
                        $tagihan->no_wa
                    );

                    // Check the response and update status
                    if (isset($response->status) && ($response->status === true || $response->status === 'true')) {
                        // Update tagihan status to 'Yes'
                        DB::table('tagihans')
                            ->where('id', $id)
                            ->update(['is_send' => 'Yes', 'tanggal_kirim_notif_wa' => now()]);
                        WaMessageStatusLog::create([
                            'message_id' => $response->message_id ?? ('tagihan-' . $id . '-' . uniqid()),
                            'recipient_id' => (string) $tagihan->no_wa,
                            'status' => 'sent',
                            'type' => $statusLogType,
                            'status_at' => now(),
                            'errors' => null,
                            'payload' => isset($response->raw) ? (array) $response->raw : null,
                        ]);
                        $responses[] = $tagihan->no_wa; // Collect processed WhatsApp numbers
                    } else {
                        // Collect failed IDs and error messages
                        DB::table('tagihans')
                            ->where('id', $id)
                            ->increment('retry');
                        WaMessageStatusLog::create([
                            'message_id' => 'tagihan-' . $id . '-' . uniqid(),
                            'recipient_id' => (string) $tagihan->no_wa,
                            'status' => 'failed',
                            'type' => $statusLogType,
                            'status_at' => now(),
                            'errors' => [['message' => $response->message ?? 'Unknown error']],
                            'payload' => isset($response->raw) ? (array) $response->raw : null,
                        ]);

                        $errors[] = $tagihan->no_wa; // Collect WhatsApp numbers for errors
                        $errorMessages[$tagihan->no_wa] = $response->message ?? 'Unknown error'; // Collect error message
                    }
                } else {
                    // Handle case where tagihan is not found
                    $errors[] = 'No WA not found'; // Use a default error message
                    $errorMessages['No WA not found'] = 'Tagihan not found';
                }
            } catch (\Exception $e) {
                // Collect exception messages
                $errors[] = 'Exception'; // Use a default error message
                $errorMessages['Exception'] = $e->getMessage();
                Log::error('Bulk send tagihan WA exception', [
                    'tagihan_id' => $id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Prepare the response message
        if (!empty($errors)) {
            $errorDetail = [];
            foreach ($errors as $no_wa) {
                $errorDetail[] = "No WA $no_wa: " . ($errorMessages[$no_wa] ?? 'Unknown error');
            }
            $message = 'Tagihan WA berhasil dikirim. Namun, terjadi kesalahan pada beberapa No WA. Rincian: ' . implode('; ', $errorDetail);
        } else {
            $message = 'Tagihan WA berhasil dikirim!';
        }

        return response()->json(['message' => $message]);
    }

    public function bayarTagihan(Request $request)
    {
        $updateData = [
            'tanggal_bayar' =>  date('Y-m-d H:i:s'),
            'metode_bayar' => $request->metode_bayar,
            'status_bayar' => 'Waiting Review',
            'tanggal_kirim_notif_wa' =>  date('Y-m-d H:i:s'),
            'created_by' => Auth::user()->id
        ];

        if ($request->metode_bayar == 'Transfer Bank') {
            $updateData['bank_account_id'] = $request->bank_account_id;
        }

        DB::table('tagihans')
            ->where('id', $request->tagihan_id)
            ->update($updateData);

        $tagihan = DB::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->where('tagihans.id', (int) $request->tagihan_id)
            ->select('tagihans.no_tagihan', 'pelanggans.nama', 'pelanggans.no_layanan')
            ->first();
        if ($tagihan) {
            AdminController::notifyAdminsByPermission(
                'tagihan view',
                'Tagihan menunggu review',
                'Tagihan ' . ($tagihan->no_tagihan ?? '-') . ' a/n ' . ($tagihan->nama ?? '-') . ' (' . ($tagihan->no_layanan ?? '-') . ')',
                [
                    'type' => 'tagihan',
                    'badge_key' => 'daftar_tagihan',
                    'tagihan_id' => (string) (int) $request->tagihan_id,
                ]
            );
        }

        return redirect()
            ->route('tagihans.index')
            ->with('success', __('Status pembayaran berhasil di update, Menunggu review'));
    }

    public function validasiTagihan(Request $request)
    {
        DB::beginTransaction();

        try {
            $tgl = now();

            // Ambil data tagihan
            $tagihan = DB::table('tagihans')->where('id', $request->id)->first();
            if (!$tagihan) {
                return response()->json(['message' => 'Data tagihan tidak ditemukan.'], 404);
            }

            if ($tagihan->status_bayar === 'Sudah Bayar') {
                return response()->json(['message' => 'Tagihan sudah berstatus dibayar sebelumnya.'], 400);
            }

            // Update status tagihan
            DB::table('tagihans')->where('id', $tagihan->id)->update([
                'status_bayar' => 'Sudah Bayar',
                'tanggal_review' => $tgl,
                'reviewed_by' => Auth::id(),
            ]);

            // Insert ke tabel pemasukan
            $categoryId = getInternetIncomeCategoryIdForPelanggan($tagihan->pelanggan_id);
            DB::table('pemasukans')->insert([
                'nominal' => $tagihan->total_bayar,
                'tanggal' => $tgl,
                'category_pemasukan_id' => $categoryId,
                'keterangan' => 'Pembayaran Tagihan no Tagihan ' . $tagihan->no_tagihan .
                    ' a/n ' . $tagihan->pelanggan_id .
                    ' Periode ' . $tagihan->periode,
                'referense_id' => $tagihan->id,
                'metode_bayar' => $tagihan->metode_bayar,
                'created_at' => $tgl,
                'updated_at' => $tgl,
            ]);
            applyInvestorSharingForPaidTagihan((int) $tagihan->id);

            // Cek apakah masih ada tagihan 'Belum Bayar' untuk pelanggan ini
            $cekTagihan = Tagihan::where('pelanggan_id', $tagihan->pelanggan_id)
                ->where('status_bayar', 'Belum Bayar')
                ->count();

            if ($cekTagihan < 1) {
                // Ambil data pelanggan dan paket
                $pelanggan = DB::table('pelanggans')
                    ->leftJoin('packages', 'pelanggans.paket_layanan', '=', 'packages.id')
                    ->select(
                        'packages.profile',
                        'pelanggans.router',
                        'pelanggans.mode_user',
                        'pelanggans.user_pppoe',
                        'pelanggans.user_static',
                        'pelanggans.status_berlangganan'
                    )
                    ->where('pelanggans.id', $tagihan->pelanggan_id)
                    ->first();

                if ($pelanggan) {
                    $client = setRouteTagihanByPelanggan($pelanggan->router);

                    if ($pelanggan->mode_user === 'PPOE') {
                        if ($pelanggan->status_berlangganan === 'Non Aktif') {
                            // Buka isolir PPOE
                            $data = $client->query((new Query('/ppp/secret/print'))->where('name', $pelanggan->user_pppoe))->read();
                            $idSecret = $data[0]['.id'] ?? null;

                            if ($idSecret) {
                                $existingComment = $data[0]['comment'] ?? null;
                                $comment = myrbaMergeMikrotikComment($existingComment, 'Isolir terbuka otomatis (lunas)');
                                $client->query((new Query('/ppp/secret/set'))
                                    ->equal('.id', $idSecret)
                                    ->equal('profile', $pelanggan->profile)
                                    ->equal('comment', $comment));
                                $client->query((new Query('/ppp/secret/enable'))->equal('.id', $idSecret));
                            }

                            // Remove session aktif jika ada
                            // $active = $client->query((new Query('/ppp/active/print'))->where('name', $pelanggan->user_pppoe))->read();
                            // if (!empty($active)) {
                            //     $idActive = $active[0]['.id'];
                            //     $client->query((new Query('/ppp/active/remove'))->equal('.id', $idActive));
                            // }
                        }
                    } else {
                        // Mode Static
                        $data = $client->query((new Query('/queue/simple/print'))->where('name', $pelanggan->user_static))->read();
                        $ip = explode('/', $data[0]['target'] ?? '')[0] ?? null;

                        if ($ip) {
                            $expired = $client->query((new Query('/ip/firewall/address-list/print'))
                                ->where('list', 'expired')
                                ->where('address', $ip))->read();

                            if (!empty($expired) && isset($expired[0]['.id'])) {
                                $client->query((new Query('/ip/firewall/address-list/remove'))
                                    ->equal('.id', $expired[0]['.id']));
                            }
                        }
                    }

                    // Update status pelanggan ke Aktif
                    DB::table('pelanggans')
                        ->where('id', $tagihan->pelanggan_id)
                        ->update(['status_berlangganan' => 'Aktif']);
                }
            }

            DB::commit();

            try {
                $waGateway = getWaGatewayActive();
                if ($waGateway && $waGateway->is_aktif === 'Yes' && $waGateway->is_wa_payment_active === 'Yes') {
                    // Fetch full data needed for template (pelanggan info is needed)
                    $fullTagihan = DB::table('tagihans')
                        ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
                        ->select('tagihans.*', 'pelanggans.nama', 'pelanggans.no_wa', 'pelanggans.no_layanan', 'pelanggans.jatuh_tempo')
                        ->where('tagihans.id', $tagihan->id)
                        ->first();

                    if ($fullTagihan && !empty($fullTagihan->no_wa)) {
                        sendNotifWa(
                            $waGateway->api_key,
                            $fullTagihan,
                            'payment_receipt',
                            $fullTagihan->no_wa
                        );
                    }
                }
            } catch (\Exception $e) {
                // Ignore WA error on manual validation, just log it
                \Log::error('Gagal kirim WA payment receipt manual validation', ['error' => $e->getMessage()]);
            }

            return response()->json(['message' => 'Tagihan berhasil divalidasi.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Validasi gagal: ' . $e->getMessage()
            ], 500);
        }
    }
}
