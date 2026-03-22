<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Http\Requests\{StorePelangganRequest, UpdatePelangganRequest};
use Yajra\DataTables\Facades\DataTables;
use Image;
use Illuminate\Support\Facades\DB;
use App\Models\AreaCoverage;
use App\Models\Package;
use App\Models\Settingmikrotik;
use Illuminate\Http\Request;
use \RouterOS\Query;
use \RouterOS\Client;
use \RouterOS\Exceptions\ConnectException;
use Alert;
use App\Models\SettingWeb;
use App\Models\BalanceHistory;
use App\Models\Tagihan;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Services\InventoryStockService;
use App\Services\TenantEntitlementService;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class PelangganController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:pelanggan view')->only('index', 'show', 'requestIndex', 'requestMaterials');
        $this->middleware('permission:pelanggan create')->only('create', 'store');
        $this->middleware('permission:pelanggan edit')->only('edit', 'update', 'requestMaterialsStore', 'requestMaterialsApprove');
        $this->middleware('permission:pelanggan delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (request()->ajax()) {
            $area_coverage = intval($request->query('area_coverage'));
            $packagePilihan = intval($request->query('packagePilihan'));
            $mikrotik = intval($request->query('mikrotik'));
            $tgl_daftar = $request->query('tgl_daftar');
            $fromMonth = $request->query('from_month'); // YYYY-MM
            $toMonth = $request->query('to_month');     // YYYY-MM
            $status = $request->query('status');
            $mode_user = $request->query('mode_user');

            $pelanggans = DB::table('pelanggans')
                ->leftJoin('area_coverages', 'pelanggans.coverage_area', '=', 'area_coverages.id')
                ->leftJoin('odcs', 'pelanggans.odc', '=', 'odcs.id')
                ->leftJoin('odps', 'pelanggans.odp', '=', 'odps.id')
                ->leftJoin('packages', 'pelanggans.paket_layanan', '=', 'packages.id')
                ->leftJoin('settingmikrotiks', 'pelanggans.router', '=', 'settingmikrotiks.id')
                ->select(
                    'pelanggans.*',
                    'area_coverages.kode_area',
                    'area_coverages.nama as nama_area',
                    'odcs.kode_odc',
                    'odps.kode_odp',
                    'packages.nama_layanan',
                    'packages.harga',
                    'settingmikrotiks.identitas_router'
                );

            $tenantId = (int) (auth()->user()->tenant_id ?? 0);
            $pelanggans = $pelanggans->where('pelanggans.tenant_id', $tenantId);

            $allowedAreas = getAllowedAreaCoverageIdsForUser();
            if (!empty($allowedAreas)) {
                $pelanggans = $pelanggans->whereIn('pelanggans.coverage_area', $allowedAreas);
            } else {
                $pelanggans = $pelanggans->whereRaw('1 = 0');
            }

            if (isset($area_coverage) && !empty($area_coverage)) {
                if ($area_coverage != 'All') {
                    $pelanggans = $pelanggans->where('pelanggans.coverage_area', $area_coverage);
                }
            }

            if (isset($packagePilihan) && !empty($packagePilihan)) {
                if ($packagePilihan != 'All') {
                    $pelanggans = $pelanggans->where('pelanggans.paket_layanan', $packagePilihan);
                }
            }

            if (isset($mikrotik) && !empty($mikrotik)) {
                if ($mikrotik != 'All') {
                    $pelanggans = $pelanggans->where('pelanggans.router', $mikrotik);
                }
            }

            if (!empty($fromMonth) && !empty($toMonth)) {
                $fromStart = $fromMonth . '-01 00:00:00';
                $toStartTs = strtotime($toMonth . '-01');
                $toEnd = date('Y-m-t', $toStartTs) . ' 23:59:59';
                $pelanggans = $pelanggans->whereBetween('pelanggans.tanggal_daftar', [$fromStart, $toEnd]);
            } else {
                if (isset($tgl_daftar) && !empty($tgl_daftar) && $tgl_daftar != 'All') {
                    $pelanggans = $pelanggans->whereRaw('DAY(pelanggans.tanggal_daftar) = ?', [$tgl_daftar]);
                }
            }

            if (isset($mode_user) && !empty($mode_user)) {
                if ($mode_user != 'All') {
                    $pelanggans = $pelanggans->where('pelanggans.mode_user', $mode_user);
                }
            }

            if (isset($status) && !empty($status)) {
                if ($status != 'All') {
                    $pelanggans = $pelanggans->where('pelanggans.status_berlangganan', $status);
                }
            }

            $pelanggans = $pelanggans->orderBy('pelanggans.id', 'DESC')->get();
            return Datatables::of($pelanggans)
                ->addIndexColumn()
                ->addColumn('alamat', function ($row) {
                    return str($row->alamat)->limit(100);
                })
                ->addColumn('balance', function ($row) {
                    return rupiah($row->balance);
                })
                ->addColumn('area_coverage', function ($row) {
                    return $row->kode_area . '-' . $row->nama_area;
                })->addColumn('odc', function ($row) {
                    return $row->kode_odc;
                })->addColumn('user_mikrotik', function ($row) {
                    if ($row->mode_user == 'Static') {
                        return $row->user_static;
                    } else {
                        return $row->user_pppoe;
                    }
                    return $row->user_static;
                })->addColumn('odp', function ($row) {
                    return $row->kode_odp;
                })->addColumn('package', function ($row) {
                    return $row->nama_layanan . '-' . $row->harga;
                })->addColumn('settingmikrotik', function ($row) {
                    return $row->identitas_router;
                })
                ->addColumn('action', 'pelanggans.include.action')
                ->toJson();
        }
        $areaCoverages = AreaCoverage::get();
        $package = Package::get();
        $router = Settingmikrotik::get();
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $x = DB::table('pelanggans')
            ->leftJoin('packages', 'pelanggans.paket_layanan', '=', 'packages.id')
            ->where('pelanggans.tenant_id', (int) (auth()->user()->tenant_id ?? 0))
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('pelanggans.coverage_area', $allowedAreas);
            })
            ->where('pelanggans.status_berlangganan', 'Aktif')
            ->sum('packages.harga');
        $areaCoverages = AreaCoverage::when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
            $q->whereIn('id', $allowedAreas);
        })->get();
        return view('pelanggans.index', [
            'areaCoverages' => $areaCoverages,
            'package' => $package,
            'router' => $router,
            'pendapatan' => $x
        ]);
    }

    public function requestIndex()
    {
        return view('pelanggans.request');
    }

    public function requestData()
    {
        $pelanggans = DB::table('pelanggans')
            ->select('pelanggans.*', DB::raw("DATE_FORMAT(pelanggans.created_at, '%Y-%m-%d %H:%i') as tanggal_request"))
            ->where('tenant_id', (int) (auth()->user()->tenant_id ?? 0))
            ->where('status_berlangganan', 'Menunggu')
            ->orderByDesc('id')
            ->get();

        return DataTables::of($pelanggans)
            ->addIndexColumn()
            ->addColumn('action', 'pelanggans.include.action')
            ->toJson();
    }

    public function requestMaterials($id)
    {
        $pelanggan = Pelanggan::findOrFail((int) $id);
        abort_if($pelanggan->status_berlangganan !== 'Menunggu', 404);

        $barangs = DB::table('barang')->select('id', 'nama_barang')->orderBy('nama_barang')->get();
        $investorOwners = $this->inventoryOwners();
        $materials = DB::table('pelanggan_request_materials')
            ->leftJoin('barang', 'pelanggan_request_materials.barang_id', '=', 'barang.id')
            ->leftJoin('users', 'pelanggan_request_materials.owner_user_id', '=', 'users.id')
            ->select(
                'pelanggan_request_materials.*',
                'barang.nama_barang',
                'users.name as owner_name'
            )
            ->where('pelanggan_request_materials.pelanggan_id', (int) $id)
            ->orderByDesc('pelanggan_request_materials.id')
            ->get();

        $approvedByName = null;
        if (!empty($pelanggan->material_approved_by)) {
            $approvedByName = DB::table('users')->where('id', (int) $pelanggan->material_approved_by)->value('name');
        }
        return view('pelanggans.request-materials', compact('pelanggan', 'barangs', 'investorOwners', 'materials', 'approvedByName'));
    }

    public function requestMaterialsStore(Request $request, $id)
    {
        $pelanggan = Pelanggan::findOrFail((int) $id);
        abort_if($pelanggan->status_berlangganan !== 'Menunggu', 404);

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.barang_id' => 'required|integer|exists:barang,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.owner_type' => 'required|in:office,investor',
            'items.*.owner_user_id' => 'nullable|integer',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        $items = $validated['items'];
        $investorIds = [];
        foreach ($items as $item) {
            $ownerType = strtolower(trim((string) ($item['owner_type'] ?? 'office')));
            if ($ownerType === 'investor') {
                $investorIds[] = (int) ($item['owner_user_id'] ?? 0);
            }
        }
        $investorIds = array_values(array_unique(array_filter($investorIds)));
        if (!empty($investorIds)) {
            if (count($investorIds) !== 1) {
                return redirect()->back()->withInput()->with('error', 'Untuk pemasangan pelanggan, kepemilikan investor hanya boleh dari satu investor saja.');
            }
            foreach ($items as $item) {
                $ownerType = strtolower(trim((string) ($item['owner_type'] ?? 'office')));
                if ($ownerType !== 'investor') {
                    return redirect()->back()->withInput()->with('error', 'Jika memakai barang investor, semua material wajib memakai barang investor (tidak boleh campur kantor).');
                }
            }
            $investorId = (int) $investorIds[0];
            foreach ($items as $item) {
                $barangId = (int) ($item['barang_id'] ?? 0);
                $qty = (int) ($item['qty'] ?? 0);
                if ($barangId < 1 || $qty < 1) {
                    continue;
                }
                $stockQty = InventoryStockService::getOwnerQty($barangId, 'investor', $investorId);
                if ($stockQty < $qty) {
                    $barangName = DB::table('barang')->where('id', $barangId)->value('nama_barang');
                    return redirect()->back()->withInput()->with('error', "Stok investor tidak mencukupi untuk {$barangName}.");
                }
            }
        }
        DB::transaction(function () use ($pelanggan, $items) {
            DB::table('pelanggan_request_materials')->where('pelanggan_id', (int) $pelanggan->id)->delete();
            $rows = [];
            foreach ($items as $item) {
                $ownerType = strtolower(trim((string) ($item['owner_type'] ?? 'office')));
                $ownerUserId = $ownerType === 'investor' ? (int) ($item['owner_user_id'] ?? 0) : null;
                if ($ownerType === 'investor' && empty($ownerUserId)) {
                    continue;
                }
                $rows[] = [
                    'pelanggan_id' => (int) $pelanggan->id,
                    'barang_id' => (int) $item['barang_id'],
                    'owner_type' => $ownerType,
                    'owner_user_id' => $ownerUserId ?: null,
                    'qty' => (int) $item['qty'],
                    'notes' => !empty($item['notes']) ? trim((string) $item['notes']) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (!empty($rows)) {
                DB::table('pelanggan_request_materials')->insert($rows);
            }
            DB::table('pelanggans')->where('id', (int) $pelanggan->id)->update([
                'material_status' => 'Pending',
                'material_approved_by' => null,
                'material_approved_at' => null,
                'updated_at' => now(),
            ]);
        });

        return redirect()->route('pelanggans-request.materials', (int) $pelanggan->id)->with('success', 'Material pemasangan berhasil disimpan.');
    }

    public function requestMaterialsApprove($id)
    {
        $pelanggan = Pelanggan::findOrFail((int) $id);
        abort_if($pelanggan->status_berlangganan !== 'Menunggu', 404);

        $materialsCount = DB::table('pelanggan_request_materials')->where('pelanggan_id', (int) $pelanggan->id)->count();
        if ($materialsCount < 1) {
            return redirect()->route('pelanggans-request.materials', (int) $pelanggan->id)
                ->with('error', 'Material belum diisi, tidak dapat divalidasi oleh tim gudang.');
        }

        DB::table('pelanggans')->where('id', (int) $pelanggan->id)->update([
            'material_status' => 'Approved',
            'material_approved_by' => auth()->id(),
            'material_approved_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('pelanggans-request.materials', (int) $pelanggan->id)->with('success', 'Material telah divalidasi tim gudang.');
    }

    public function estimasiPendapatan(Request $request)
    {
        $area_coverage = $request->query('area_coverage');
        $packagePilihan = $request->query('packagePilihan');
        $mikrotik = $request->query('mikrotik');
        $tgl_daftar = $request->query('tgl_daftar');
        $mode_user = $request->query('mode_user');
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        $query = DB::table('pelanggans')
            ->leftJoin('packages', 'pelanggans.paket_layanan', '=', 'packages.id')
            ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                $q->whereIn('pelanggans.coverage_area', $allowedAreas);
            })
            ->where('pelanggans.status_berlangganan', 'Aktif');
        if (!empty($area_coverage) && $area_coverage !== 'All') {
            $query->where('pelanggans.coverage_area', intval($area_coverage));
        }
        if (!empty($packagePilihan) && $packagePilihan !== 'All') {
            $query->where('pelanggans.paket_layanan', intval($packagePilihan));
        }
        if (!empty($mikrotik) && $mikrotik !== 'All') {
            $query->where('pelanggans.router', intval($mikrotik));
        }
        if (!empty($tgl_daftar) && $tgl_daftar !== 'All') {
            $query->whereRaw('DAY(pelanggans.tanggal_daftar) = ?', [$tgl_daftar]);
        }
        if (!empty($mode_user) && $mode_user !== 'All') {
            $query->where('pelanggans.mode_user', $mode_user);
        }
        $sum = $query->sum('packages.harga');
        return response()->json(['pendapatan' => rupiah($sum)]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('pelanggans.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePelangganRequest $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $currentPelanggan = (int) DB::table('pelanggans')->where('tenant_id', $tenantId)->count();
        try {
            TenantEntitlementService::ensureQuota('max_pelanggans', $currentPelanggan, 1, 'pelanggan');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        $newPelanggan = null;
        $waWarning = null;
        DB::beginTransaction();
        try {
            $attr = $request->validated();
            $attr['password'] = bcrypt($request->password);
            $attr['tenant_id'] = $tenantId;

            if ($request->file('photo_ktp') && $request->file('photo_ktp')->isValid()) {
                $path = storage_path('app/public/uploads/photo_ktps/');
                $filename = $request->file('photo_ktp')->hashName();

                if (!file_exists($path)) {
                    mkdir($path, 0777, true);
                }
                Image::make($request->file('photo_ktp')->getRealPath())->resize(500, 500, function ($constraint) {
                    $constraint->upsize();
                    $constraint->aspectRatio();
                })->save($path . $filename);

                $attr['photo_ktp'] = $filename;
            }

            $newPelanggan = Pelanggan::create($attr);


            if ($request->has('generate_tagihan') && $request->generate_tagihan == '1') {
                $paket = \App\Models\Package::find($newPelanggan->paket_layanan);
                $nominal = $paket->harga;
                $nominalPpn = 0;

                if ($newPelanggan->ppn == 'Yes') {
                    // PPN 11%
                    $nominalPpn = $nominal * 0.11;
                }

                $totalBayar = $nominal + $nominalPpn;

                // Kode pembuatan tagihan sekarang ada DI DALAM blok if
                $tagihan = Tagihan::create([
                    'no_tagihan' => 'INV/' . date('Ymd') . '/' . strtoupper(Str::random(6)),
                    'pelanggan_id' => $newPelanggan->id,
                    'periode' => \Carbon\Carbon::parse($newPelanggan->tanggal_daftar)->format('Y-m'),
                    'status_bayar' => 'Belum Bayar',
                    'nominal_bayar' => $nominal,
                    'potongan_bayar' => 0,
                    'ppn' => $newPelanggan->ppn,
                    'nominal_ppn' => $nominalPpn,
                    'total_bayar' => $totalBayar,
                    'tanggal_create_tagihan' => now(),
                    'is_send' => 'No',
                    'created_by' => auth()->id(),
                ]);
                autoPayTagihanWithSaldo($newPelanggan->id);
                
                // Auto send WA notification if active
                autoSendTagihanWa($tagihan->id);
            }


            applyReferralBonusIfEligible((int) $newPelanggan->id);

            DB::commit();

            try {
                $getWaGatewayActive = getWaGatewayActive();
                if ($getWaGatewayActive && $getWaGatewayActive->is_aktif === 'Yes' && $getWaGatewayActive->is_wa_welcome_active === 'Yes' && $newPelanggan->status_berlangganan === 'Aktif' && !empty($newPelanggan->no_wa)) {
                    $waResponse = sendNotifWa(
                        $getWaGatewayActive->api_key,
                        $newPelanggan,
                        'welcome_registration',
                        $newPelanggan->no_wa
                    );
                    if (!isset($waResponse->status) || !($waResponse->status === true || $waResponse->status === 'true')) {
                        $waWarning = $waResponse->message ?? 'Notifikasi WA pendaftaran gagal dikirim.';
                    }
                }
            } catch (\Throwable $waError) {
                Log::warning('Notifikasi WA pendaftaran gagal', [
                    'pelanggan_id' => $newPelanggan?->id,
                    'no_wa' => $newPelanggan?->no_wa,
                    'error' => $waError->getMessage(),
                ]);
                $waWarning = $waError->getMessage();
            }

            $successMessage = __('Pelanggan berhasil dibuat.');
            if ($waWarning) {
                $successMessage .= ' ' . __('Notifikasi WA: ') . $waWarning;
            }
            return redirect()
                ->route('pelanggans.index')
                ->with('success', $successMessage);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal menyimpan data pelanggan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Pelanggan $pelanggan
     * @return \Illuminate\Http\Response
     */
    public function show(Pelanggan $pelanggan)
    {
        // Mengambil data detail pelanggan seperti sebelumnya
        $detailPelanggan = DB::table('pelanggans')
            ->leftJoin('area_coverages', 'pelanggans.coverage_area', '=', 'area_coverages.id')
            ->leftJoin('odcs', 'pelanggans.odc', '=', 'odcs.id')
            ->leftJoin('odps', 'pelanggans.odp', '=', 'odps.id')
            ->leftJoin('packages', 'pelanggans.paket_layanan', '=', 'packages.id')
            ->leftJoin('settingmikrotiks', 'pelanggans.router', '=', 'settingmikrotiks.id')
            ->select(
                'pelanggans.*',
                'area_coverages.kode_area',
                'area_coverages.nama as nama_area',
                'odcs.kode_odc',
                'odps.kode_odp',
                'packages.nama_layanan',
                'packages.harga',
                'settingmikrotiks.identitas_router'
            )->where('pelanggans.id', $pelanggan->id)->first();

        // Menghitung total pendapatan dari referral
        $totalPendapatanReferral = BalanceHistory::where('pelanggan_id', $pelanggan->id)
            ->where('description', 'LIKE', '%fee referal%')
            ->sum('amount');

        // Menghitung jumlah orang yang menggunakan kode referral pelanggan ini
        $jumlahPenggunaReferral = Pelanggan::where('kode_referal', $pelanggan->no_layanan)->count();

        $deviceReturns = [];
        if (DB::getSchemaBuilder()->hasTable('pelanggan_device_returns')) {
            $deviceReturns = DB::table('pelanggan_device_returns as r')
                ->leftJoin('users as u', 'r.created_by', '=', 'u.id')
                ->leftJoin('transaksi as t', 'r.transaksi_in_id', '=', 't.id')
                ->where('r.pelanggan_id', (int) $pelanggan->id)
                ->select(
                    'r.*',
                    'u.name as created_by_name',
                    't.kode_transaksi as transaksi_kode'
                )
                ->orderByDesc('r.id')
                ->limit(20)
                ->get();
        }

        // Mengirim semua data ke view
        return view('pelanggans.show', [
            'pelanggan' => $detailPelanggan,
            'totalPendapatanReferral' => $totalPendapatanReferral,
            'jumlahPenggunaReferral' => $jumlahPenggunaReferral,
            'deviceReturns' => $deviceReturns,
        ]);
    }

    public function edit(Pelanggan $pelanggan)
    {
        $pelanggan->load('area_coverage:id,kode_area', 'odc:id,kode_odc', 'odp:id,kode_odc', 'package:id,nama_layanan', 'settingmikrotik:id,identitas_router');
        $dataOdcs = DB::table('odcs')->where('wilayah_odc',  $pelanggan->coverage_area)->get();
        $dataodps = DB::table('odps')->where('kode_odc',  $pelanggan->odc)->get();
        $dataPort = DB::table('odps')->where('id', $pelanggan->odp)->first();
        $array = [];
        if ($dataPort) {
            $jmlPort = $dataPort->jumlah_port;
            for ($x = 1; $x <=  $jmlPort; $x++) {
                // find customer
                $cek = DB::table('pelanggans')
                    ->where('odp', $pelanggan->odp)
                    ->where('no_port_odp', $x)
                    ->first();
                if ($cek) {
                    $array[$x] = $cek->no_layanan . ' - ' . $cek->nama;
                } else {
                    $array[$x] = 'Kosong';
                }
            }
        }
        $router = DB::table('settingmikrotiks')->where('id', $pelanggan->router)->first();
        if ($router) {
            try {
                $client = new Client([
                    'host' => $router->host,
                    'user' => $router->username,
                    'pass' => $router->password,
                    'port' => (int) $router->port,
                ]);
            } catch (ConnectException $e) {
                echo $e->getMessage() . PHP_EOL;
                die();
            }
            $query = new Query('/ppp/secret/print');
            $secretPPoe = $client->query($query)->read();
        } else {
            $secretPPoe = [];
        }

        if ($router) {
            try {
                $clientstatik = new Client([
                    'host' => $router->host,
                    'user' => $router->username,
                    'pass' => $router->password,
                    'port' => (int) $router->port,
                ]);
            } catch (ConnectException $e) {
                echo $e->getMessage() . PHP_EOL;
                die();
            }
            $querystatik = (new Query('/queue/simple/print'))
                ->where('dynamic', 'false');
            $statik = $clientstatik->query($querystatik)->read();
        } else {
            $statik = [];
        }
        return view('pelanggans.edit', compact('pelanggan', 'dataOdcs', 'dataodps', 'array', 'secretPPoe', 'statik'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Pelanggan $pelanggan
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePelangganRequest $request, Pelanggan $pelanggan)
    {
        $attr = $request->validated();
        $statusLama = $pelanggan->status_berlangganan;
        $waWarning = null;
        $packageNotice = null;
        $shouldCreateTagihan = false;
        $createTagihanPeriode = null;
        $requestedPackageId = null;

        if ($request->mode_user != $pelanggan->mode_user) {
            if ($request->mode_user == 'Static') {
                $attr['user_pppoe'] = null;
            } else {
                $attr['user_static'] = null;
            }
        }

        switch (is_null($request->password)) {
            case true:
                unset($attr['password']);
                break;
            default:
                $attr['password'] = bcrypt($request->password);
                break;
        }

        if ($request->file('photo_ktp') && $request->file('photo_ktp')->isValid()) {

            $path = storage_path('app/public/uploads/photo_ktps/');
            $filename = $request->file('photo_ktp')->hashName();

            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            Image::make($request->file('photo_ktp')->getRealPath())->resize(500, 500, function ($constraint) {
                $constraint->upsize();
                $constraint->aspectRatio();
            })->save($path . $filename);

            // delete old photo_ktp from storage
            if ($pelanggan->photo_ktp != null && file_exists($path . $pelanggan->photo_ktp)) {
                unlink($path . $pelanggan->photo_ktp);
            }

            $attr['photo_ktp'] = $filename;
        }

        if ($statusLama === 'Menunggu' && ($attr['status_berlangganan'] ?? 'Menunggu') !== 'Menunggu') {
            $attr['tanggal_daftar'] = now()->toDateString();
        }
        $toValidateFromRequest = $statusLama === 'Menunggu' && ($attr['status_berlangganan'] ?? 'Menunggu') !== 'Menunggu';
        if ($toValidateFromRequest) {
            $materialsCount = DB::table('pelanggan_request_materials')->where('pelanggan_id', (int) $pelanggan->id)->count();
            if ($materialsCount < 1) {
                return redirect()
                    ->route('pelanggans-request.materials', (int) $pelanggan->id)
                    ->with('error', 'Sebelum validasi pelanggan baru, bagian gudang wajib mengisi material pemasangan.');
            }
            $materialStatus = (string) (DB::table('pelanggans')->where('id', (int) $pelanggan->id)->value('material_status') ?? 'Pending');
            if (strtolower(trim($materialStatus)) !== 'approved') {
                return redirect()
                    ->route('pelanggans-request.materials', (int) $pelanggan->id)
                    ->with('error', 'Sebelum validasi pelanggan baru, material wajib divalidasi tim gudang.');
            }
        }

        try {
            $requestedPackageId = isset($attr['paket_layanan']) ? (int) $attr['paket_layanan'] : null;
            $currentPackageId = !empty($pelanggan->paket_layanan) ? (int) $pelanggan->paket_layanan : null;
            if ($requestedPackageId && $currentPackageId && $requestedPackageId !== $currentPackageId) {
                $unpaidCount = DB::table('tagihans')
                    ->where('pelanggan_id', (int) $pelanggan->id)
                    ->whereNotIn('status_bayar', ['Sudah Bayar', 'PAID', 'Paid'])
                    ->count();
                if ($unpaidCount > 0) {
                    return redirect()->back()->withInput()->with('error', 'Tidak bisa ganti paket karena masih ada tunggakan.');
                }

                $tanggalDaftarRaw = $pelanggan->getRawOriginal('tanggal_daftar');
                $jatuhTempo = (int) ($pelanggan->jatuh_tempo ?? 0);
                $upcoming = myrbaUpcomingBillingPeriodForPelanggan($tanggalDaftarRaw, $jatuhTempo, now());
                $periodeBerjalan = $upcoming['periode'] ?? now()->format('Y-m');
                $tagihanExists = DB::table('tagihans')
                    ->where('pelanggan_id', (int) $pelanggan->id)
                    ->where('periode', $periodeBerjalan)
                    ->exists();

                if (!$tagihanExists) {
                    $shouldCreateTagihan = (($pelanggan->is_generate_tagihan ?? 'Yes') === 'Yes');
                    $createTagihanPeriode = $periodeBerjalan;
                    $attr['pending_paket_layanan'] = null;
                    $attr['pending_paket_effective_periode'] = null;
                    $attr['pending_paket_requested_by'] = null;
                    $attr['pending_paket_requested_at'] = null;
                    $attr['pending_paket_note'] = null;
                    $packageNotice = 'Paket berhasil diubah. Tagihan periode ' . $periodeBerjalan . ' dibuat mengikuti paket baru.';
                } else {
                    $next = myrbaNextBillingPeriodForPelanggan($tanggalDaftarRaw, $jatuhTempo, now());
                    $effectivePeriode = $next['periode'] ?? now()->addMonth()->format('Y-m');
                    $attr['paket_layanan'] = $currentPackageId;
                    $attr['pending_paket_layanan'] = $requestedPackageId;
                    $attr['pending_paket_effective_periode'] = $effectivePeriode;
                    $attr['pending_paket_requested_by'] = auth()->id();
                    $attr['pending_paket_requested_at'] = now();
                    $packageNotice = 'Paket dijadwalkan mulai periode ' . $effectivePeriode . '.';
                }
            }

            DB::transaction(function () use ($pelanggan, $attr, $toValidateFromRequest, $shouldCreateTagihan, $createTagihanPeriode, $requestedPackageId) {
                $pelanggan->update($attr);
                if ($toValidateFromRequest) {
                    $this->createStockOutFromRequestMaterial($pelanggan);
                }
                if ($shouldCreateTagihan && $createTagihanPeriode && $requestedPackageId) {
                    $this->createTagihanForPaketChange((int) $pelanggan->id, (string) $createTagihanPeriode, (int) $requestedPackageId);
                }
            });
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Validasi pelanggan gagal: ' . $e->getMessage());
        }

        if ($statusLama === 'Menunggu' && $pelanggan->status_berlangganan === 'Aktif') {
            $waRes = autoSendWelcomeWa($pelanggan->id);
            if (is_array($waRes) && empty($waRes['ok'])) {
                $waWarning = $waRes['message'] ?? 'Notifikasi WA pelanggan baru gagal dikirim.';
            }
        }
        if ($pelanggan->status_berlangganan === 'Aktif') {
            applyReferralBonusIfEligible((int) $pelanggan->id);
        }

        $successMessage = __('The pelanggan was updated successfully.');
        if ($waWarning) {
            $successMessage .= ' ' . __('Notifikasi WA: ') . $waWarning;
        }
        if ($packageNotice) {
            $successMessage .= ' ' . $packageNotice;
        }
        return redirect()
            ->route('pelanggans.index')
            ->with('success', $successMessage);
    }

    private function createTagihanForPaketChange(int $pelangganId, string $periode, int $packageId): void
    {
        $periode = trim($periode);
        if (!preg_match('/^\d{4}-\d{2}$/', $periode)) {
            return;
        }
        $exists = DB::table('tagihans')->where('pelanggan_id', $pelangganId)->where('periode', $periode)->exists();
        if ($exists) {
            return;
        }
        $pelanggan = DB::table('pelanggans')->where('id', $pelangganId)->lockForUpdate()->first();
        if (!$pelanggan) {
            return;
        }
        if (($pelanggan->is_generate_tagihan ?? 'Yes') !== 'Yes') {
            return;
        }
        $paket = DB::table('packages')->where('id', $packageId)->first();
        if (!$paket) {
            return;
        }
        $harga = (int) ($paket->harga ?? 0);
        $ppn = (string) ($pelanggan->ppn ?? 'No');
        $nominalPpn = $ppn === 'Yes' ? (int) round($harga * 0.11) : 0;
        $totalBayar = $harga + $nominalPpn;

        $noTagihan = 'INV-SSL-' . Str::upper(Str::random(10));
        DB::table('tagihans')->insert([
            'no_tagihan' => $noTagihan,
            'pelanggan_id' => $pelangganId,
            'periode' => $periode,
            'status_bayar' => 'Belum Bayar',
            'nominal_bayar' => $harga,
            'potongan_bayar' => 0,
            'ppn' => $ppn,
            'nominal_ppn' => $nominalPpn,
            'total_bayar' => $totalBayar,
            'tanggal_create_tagihan' => now(),
            'is_send' => 'No',
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        autoPayTagihanWithSaldo($pelangganId);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Pelanggan $pelanggan
     * @return \Illuminate\Http\Response
     */
    public function destroy(Pelanggan $pelanggan)
    {
        try {
            $path = storage_path('app/public/uploads/photo_ktps/');

            if ($pelanggan->photo_ktp != null && file_exists($path . $pelanggan->photo_ktp)) {
                unlink($path . $pelanggan->photo_ktp);
            }

            $pelanggan->delete();

            return redirect()
                ->route('pelanggans.index')
                ->with('success', __('The pelanggan was deleted successfully.'));
        } catch (\Throwable $th) {
            return redirect()
                ->route('pelanggans.index')
                ->with('error', __("The pelanggan can't be deleted because it's related to another table."));
        }
    }

    public function setToExpiredStatic($pelanggan_id, $user_static)
    {
        try {
            $pelanggan = DB::table('pelanggans')
                ->select('id', 'router')
                ->where('id', $pelanggan_id)
                ->first();
            $client = $pelanggan ? setRouteTagihanByPelanggan($pelanggan->router) : null;
            if (!$client) {
                return redirect()
                    ->route('pelanggans.index')
                    ->with('error', __('Router pelanggan tidak ditemukan.'));
            }

            // get ip by user static
            $queryGet = (new Query('/queue/simple/print'))
                ->where('name', $user_static);
            $data = $client->query($queryGet)->read();
            if (empty($data) || !isset($data[0]['target'])) {
                return redirect()
                    ->route('pelanggans.index')
                    ->with('error', __('User static tidak ditemukan di router.'));
            }
            $ip = $data[0]['target'];
            $parts = explode('/', $ip);
            $fixIp = $parts[0];

            $queryAdd = (new Query('/ip/firewall/address-list/add'))
                ->equal('list', 'expired')
                ->equal('address', $fixIp);
            $client->query($queryAdd)->read();
            // update status pelanggan jadi non aktif
            $affected = DB::table('pelanggans')
                ->where('id', $pelanggan_id)
                ->update(['status_berlangganan' => 'Non Aktif']);
            return redirect()
                ->route('pelanggans.index')
                ->with('success', __('Internet pelanggan berhasil di set Expired'));
        } catch (ConnectException $e) {
            echo $e->getMessage() . PHP_EOL;
            die();
        }
    }

    public function setNonToExpiredStatic($pelanggan_id, $user_static)
    {
        try {
            $pelanggan = DB::table('pelanggans')
                ->select('id', 'router')
                ->where('id', $pelanggan_id)
                ->first();
            $client = $pelanggan ? setRouteTagihanByPelanggan($pelanggan->router) : null;
            if (!$client) {
                return redirect()
                    ->route('pelanggans.index')
                    ->with('error', __('Router pelanggan tidak ditemukan.'));
            }
            // get ip by user static
            $queryGet = (new Query('/queue/simple/print'))
                ->where('name', $user_static);
            $data = $client->query($queryGet)->read();
            if (empty($data) || !isset($data[0]['target'])) {
                return redirect()
                    ->route('pelanggans.index')
                    ->with('error', __('User static tidak ditemukan di router.'));
            }
            $ip = $data[0]['target'];
            $parts = explode('/', $ip);
            $fixIp = $parts[0];
            // get id
            $queryGet = (new Query('/ip/firewall/address-list/print'))
                ->where('list', 'expired') // Filter by name
                ->where('address', $fixIp);
            $data = $client->query($queryGet)->read();

            if (isset($data[0]['.id'])) {
                $idIP = $data[0]['.id'];
                $queryRemove = (new Query('/ip/firewall/address-list/remove'))
                    ->equal('.id', $idIP);
                $client->query($queryRemove)->read();
            }
            // update status pelanggan jadi non aktif
            $affected = DB::table('pelanggans')
                ->where('id', $pelanggan_id)
                ->update(['status_berlangganan' => 'Aktif']);
            return redirect()
                ->route('pelanggans.index')
                ->with('success', __('Internet pelanggan berhasil di set Tidak Expired 2'));
        } catch (ConnectException $e) {
            echo $e->getMessage() . PHP_EOL;
            die();
        }
    }

    public function setToExpired($pelanggan_id, $user_pppoe)
    {
        try {
            $pelanggan = DB::table('pelanggans')
                ->select('id', 'router')
                ->where('id', $pelanggan_id)
                ->first();
            $client = $pelanggan ? setRouteTagihanByPelanggan($pelanggan->router) : null;
            if (!$client) {
                return redirect()
                    ->route('pelanggans.index')
                    ->with('error', __('Router pelanggan tidak ditemukan.'));
            }
            $queryGet = (new Query('/ppp/secret/print'))
                ->where('name', $user_pppoe);
            $data = $client->query($queryGet)->read();
            if (empty($data) || !isset($data[0]['.id'])) {
                return redirect()
                    ->route('pelanggans.index')
                    ->with('error', __('User PPPoE tidak ditemukan di router.'));
            }
            $idSecret = $data[0]['.id'];
            $existingComment = $data[0]['comment'] ?? null;
            $comment = myrbaMergeMikrotikComment($existingComment, 'Set PPPoE Expired');
            $queryComment = (new Query('/ppp/secret/set'))
                ->equal('.id', $idSecret)
                ->equal('comment', $comment);
            $client->query($queryComment)->read();
            $client->query((new Query('/ppp/secret/disable'))->equal('.id', $idSecret))->read();
            // get name from active ppp
            $queryGet = (new Query('/ppp/active/print'))
                ->where('name', $user_pppoe);
            $dataActive = $client->query($queryGet)->read();
            // remove session
            if (!empty($dataActive) && isset($dataActive[0]['.id'])) {
                $idActive = $dataActive[0]['.id'];
                $queryDelete = (new Query('/ppp/active/remove'))
                    ->equal('.id', $idActive);
                $client->query($queryDelete)->read();
            }
            // update status pelanggan jadi non aktif
            $affected = DB::table('pelanggans')
                ->where('id', $pelanggan_id)
                ->update(['status_berlangganan' => 'Non Aktif']);

            return redirect()
                ->route('pelanggans.index')
                ->with('success', __('Internet pelanggan berhasil di set Expired'));
        } catch (ConnectException $e) {
            echo $e->getMessage() . PHP_EOL;
            die();
        }
    }

    public function setNonToExpired($pelanggan_id, $user_pppoe)
    {

        try {
            $pelangganData = DB::table('pelanggans')
                ->leftJoin('packages', 'pelanggans.paket_layanan', '=', 'packages.id')
                ->select('pelanggans.router', 'packages.profile')
                ->where('pelanggans.id', $pelanggan_id)
                ->first();
            $client = $pelangganData ? setRouteTagihanByPelanggan($pelangganData->router) : null;
            if (!$client) {
                return redirect()
                    ->route('pelanggans.index')
                    ->with('error', __('Router pelanggan tidak ditemukan.'));
            }
            $queryGet = (new Query('/ppp/secret/print'))
                ->where('name', $user_pppoe);
            $data = $client->query($queryGet)->read();
            if (empty($data) || !isset($data[0]['.id'])) {
                return redirect()
                    ->route('pelanggans.index')
                    ->with('error', __('User PPPoE tidak ditemukan di router.'));
            }
            $idSecret = $data[0]['.id'];
            // balikan paket
            $existingComment = $data[0]['comment'] ?? null;
            $comment = myrbaMergeMikrotikComment($existingComment, 'Unset PPPoE Expired');
            $queryComment = (new Query('/ppp/secret/set'))
                ->equal('.id', $idSecret)
                ->equal('profile', $pelangganData->profile ?? 'default')
                ->equal('comment', $comment);
            $client->query($queryComment)->read();
            $client->query((new Query('/ppp/secret/enable'))->equal('.id', $idSecret))->read();
            // get name
            $queryGet = (new Query('/ppp/active/print'))
                ->where('name', $user_pppoe);
            $data = $client->query($queryGet)->read();
            // remove session
            if (!empty($data) && isset($data[0]['.id'])) {
                $idActive = $data[0]['.id'];
                $queryDelete = (new Query('/ppp/active/remove'))
                    ->equal('.id', $idActive);
                $client->query($queryDelete)->read();
            }

            // update status pelanggan jadi aktif
            $affected = DB::table('pelanggans')
                ->where('id', $pelanggan_id)
                ->update(['status_berlangganan' => 'Aktif']);

            return redirect()
                ->route('pelanggans.index')
                ->with('success', __('Internet pelanggan berhasil di set Tidak Expired'));
        } catch (ConnectException $e) {
            echo $e->getMessage() . PHP_EOL;
            die();
        }
    }

    public function getTableArea($id)
    {
        $allowedAreas = getAllowedAreaCoverageIdsForUser();
        if (!in_array((int) $id, $allowedAreas, true)) {
            $message = 'Tidak diizinkan';
            $data = [];
            return response()->json(compact('message', 'data'), 403);
        }
        $data = DB::table('pelanggans')->where('coverage_area', $id)->get();
        $message = 'Berhasil mengambil data kota';
        return response()->json(compact('message', 'data'));
    }

    public function getTableOdc($id)
    {
        $data = DB::table('pelanggans')->where('odc', $id)->get();
        $message = 'Berhasil mengambil data kota';
        return response()->json(compact('message', 'data'));
    }

    public function getTableOdp($id)
    {
        $data = DB::table('pelanggans')->where('odp', $id)->get();
        $message = 'Berhasil mengambil data kota';
        return response()->json(compact('message', 'data'));
    }

    private function inventoryOwners()
    {
        return DB::table('users')
            ->leftJoin('investor_share_rules', 'users.id', '=', 'investor_share_rules.user_id')
            ->leftJoin('model_has_roles', function ($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', '=', 'App\\Models\\User');
            })
            ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where(function ($q) {
                $q->whereNotNull('investor_share_rules.id')
                    ->orWhereRaw('LOWER(roles.name) like ?', ['%investor%'])
                    ->orWhereRaw('LOWER(roles.name) like ?', ['%mitra%']);
            })
            ->select('users.id', 'users.name')
            ->distinct()
            ->orderBy('users.name')
            ->get();
    }

    private function createStockOutFromRequestMaterial(Pelanggan $pelanggan): void
    {
        $materials = DB::table('pelanggan_request_materials')
            ->where('pelanggan_id', (int) $pelanggan->id)
            ->get();
        if ($materials->isEmpty()) {
            return;
        }

        $investorIds = $materials->where('owner_type', 'investor')->pluck('owner_user_id')->filter()->map(fn ($v) => (int) $v)->unique()->values()->all();
        if (!empty($investorIds) && count($investorIds) !== 1) {
            throw new \RuntimeException('Material pemasangan hanya boleh dari satu investor.');
        }

        $kode = 'TR-OUT-REQ-' . date('YmdHis') . '-' . (int) $pelanggan->id;
        $transaksi = Transaksi::create([
            'user_id' => auth()->id(),
            'kode_transaksi' => $kode,
            'tanggal_transaksi' => now()->toDateString(),
            'jenis_transaksi' => 'out',
            'keterangan' => 'Pemasangan baru atas nama ' . ($pelanggan->nama ?? '-'),
        ]);

        foreach ($materials as $m) {
            $ownerType = strtolower(trim((string) ($m->owner_type ?? 'office')));
            $ownerType = $ownerType === 'investor' ? 'investor' : 'office';
            $ownerUserId = $ownerType === 'investor' ? (int) ($m->owner_user_id ?? 0) : null;
            $qty = (int) ($m->qty ?? 0);
            $barangId = (int) ($m->barang_id ?? 0);
            if ($qty < 1 || $barangId < 1) {
                continue;
            }
            $pricing = InventoryStockService::getOwnerPricing($barangId, $ownerType, $ownerUserId ?: null);
            $ok = InventoryStockService::decrease($barangId, $ownerType, $ownerUserId ?: null, $qty);
            if (!$ok) {
                $barangName = DB::table('barang')->where('id', $barangId)->value('nama_barang');
                $ownerName = $ownerUserId ? DB::table('users')->where('id', $ownerUserId)->value('name') : null;
                $ownerLabel = InventoryStockService::ownerLabel($ownerType, $ownerName);
                throw new \RuntimeException("Stok material tidak cukup untuk {$barangName} ({$ownerLabel}).");
            }
            TransaksiDetail::create([
                'transaksi_id' => (int) $transaksi->id,
                'barang_id' => $barangId,
                'owner_type' => $ownerType,
                'owner_user_id' => $ownerUserId ?: null,
                'source_type' => 'pelanggan_request',
                'source_id' => (int) $pelanggan->id,
                'jumlah' => $qty,
                'hpp_unit' => (int) ($pricing['hpp_unit'] ?? 0),
                'harga_jual_unit' => (int) ($pricing['harga_jual_unit'] ?? 0),
                'purpose' => 'install',
                'purpose_scope' => 'pelanggan',
                'target_pelanggan_id' => (int) $pelanggan->id,
            ]);
        }

        if (!empty($investorIds)) {
            $this->attachPelangganToInvestorShare((int) $pelanggan->id, (int) $investorIds[0]);
        }
    }

    private function attachPelangganToInvestorShare(int $pelangganId, int $investorUserId): void
    {
        if (!DB::getSchemaBuilder()->hasTable('investor_share_rules') || !DB::getSchemaBuilder()->hasTable('investor_share_rule_pelanggans')) {
            return;
        }
        $existing = DB::table('investor_share_rule_pelanggans as rp')
            ->join('investor_share_rules as r', 'rp.rule_id', '=', 'r.id')
            ->where('rp.pelanggan_id', $pelangganId)
            ->where('rp.is_included', 'Yes')
            ->select('r.user_id')
            ->first();
        if ($existing && (int) $existing->user_id !== $investorUserId) {
            throw new \RuntimeException('Pelanggan sudah terikat dengan investor lain.');
        }
        $rule = DB::table('investor_share_rules')
            ->where('user_id', $investorUserId)
            ->where('is_aktif', 'Yes')
            ->orderByRaw("CASE WHEN rule_type = 'per_customer' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();
        if (!$rule) {
            throw new \RuntimeException('Rule bagi hasil investor belum tersedia untuk investor ini.');
        }
        DB::table('investor_share_rule_pelanggans')->updateOrInsert(
            ['rule_id' => (int) $rule->id, 'pelanggan_id' => $pelangganId],
            ['is_included' => 'Yes', 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function updateGenerateTagihan(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:pelanggans,id',
            'is_generate_tagihan' => 'required|in:Yes,No',
        ]);

        $pelanggan = Pelanggan::findOrFail($request->id);
        $pelanggan->is_generate_tagihan = $request->is_generate_tagihan;
        $pelanggan->save();

        return response()->json([
            'success' => true,
            'message' => __('Tagihan status updated successfully.')
        ]);
    }

    public function searchPelanggan(Request $request)
    {
        $search = $request->term;
        $pelanggans = Pelanggan::where('nama', 'LIKE', "%{$search}%")
            ->orWhere('no_layanan', 'LIKE', "%{$search}%")
            ->limit(10)
            ->get(['id', 'nama', 'no_layanan']);
        return response()->json($pelanggans);
    }

    public function cetakSurat($id)
    {
        $data = Pelanggan::leftJoin('packages', 'pelanggans.paket_layanan', '=', 'packages.id')
            ->select('pelanggans.*', 'packages.nama_layanan', 'packages.harga')
            ->where('pelanggans.id', $id)
            ->firstOrFail();
        
        $settingWeb = SettingWeb::first();
        
        return view('pelanggans.print_surat', compact('data', 'settingWeb'));
    }
}
