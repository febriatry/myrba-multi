<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use App\Models\SettingWeb;
use App\Services\TenantEntitlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Image;

class WebController extends Controller
{
    public function index(Request $request)
    {
        // Jika tidak ada request 'no_tagihan', tampilkan landing page
        if (! $request->has('no_tagihan')) {
            return view('layouts.frontend.landing');
        }

        $no_tagihan = $request->no_tagihan ?? '';
        $metodeBayar = [];

        $settingWeb = SettingWeb::first(); // ✅ Ambil setting
        $tenantId = (int) $request->query('tid', 0);
        $noLayanan = trim((string) $no_tagihan);
        if ($tenantId < 1) {
            [$tidGuess, $nl] = parsePrefixedNoLayanan($noLayanan);
            if ($tidGuess > 0 && $nl !== '') {
                $tenantId = $tidGuess;
                $noLayanan = $nl;
            } else {
                $tenantId = resolveTenantIdFromNoLayanan($noLayanan);
            }
        } else {
            [, $nl] = parsePrefixedNoLayanan($noLayanan);
            if ($nl !== '') {
                $noLayanan = $nl;
            }
        }
        if (! TenantEntitlementService::featureEnabledForTenantId($tenantId, 'payment_gateway', false)) {
            return view('frontend.index', [
                'no_tagihan' => $no_tagihan,
                'tagihan' => null,
                'tagihanCount' => 0,
                'metodeBayar' => [],
                'settingWeb' => $settingWeb,
            ])->with('error', 'Payment gateway tidak tersedia untuk tenant ini.');
        }

        $tagihan = DB::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->select('tagihans.*', 'pelanggans.nama', 'pelanggans.no_layanan')
            ->where('pelanggans.no_layanan', '=', $noLayanan)
            ->where('tagihans.status_bayar', '=', 'Belum Bayar')
            ->orderBy('tagihans.id', 'asc')
            ->first();

        if ($tagihan) {
            $tagihanCount = DB::table('tagihans')
                ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
                ->where('pelanggans.no_layanan', '=', $noLayanan)
                ->where('tagihans.status_bayar', '=', 'Belum Bayar')
                ->count();

            if ($tagihan->status_bayar == 'Belum Bayar') {
                $tripay = resolveTripayConfigForTenantId($tenantId);
                if (! $tripay) {
                    return view('frontend.index', [
                        'no_tagihan' => $no_tagihan,
                        'tagihan' => $tagihan,
                        'tagihanCount' => $tagihanCount,
                        'metodeBayar' => [],
                        'settingWeb' => $settingWeb,
                    ])->with('error', 'Konfigurasi Tripay belum diisi oleh tenant.');
                }

                $url = $tripay['base_url'].'merchant/payment-channel';
                $api_key = $tripay['api_key'];

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.$api_key,
                ])->get($url);

                $a = json_decode($response->getBody());
                if ($a->success == true) {
                    $metodeBayar = $a->data;
                } else {
                    echo $a->message;
                    exit();
                }
            }
        } else {
            $tagihan = DB::table('tagihans')
                ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
                ->select('tagihans.*', 'pelanggans.nama', 'pelanggans.no_layanan')
                ->where('pelanggans.no_layanan', '=', $noLayanan)
                ->orderBy('tagihans.id', 'desc')
                ->first();
            $tagihanCount = 0;
        }

        return view('frontend.index', [
            'no_tagihan' => $no_tagihan,
            'tagihan' => $tagihan,
            'tagihanCount' => $tagihanCount,
            'metodeBayar' => $metodeBayar,
            'settingWeb' => $settingWeb, // ✅ kirim ke view
        ]);
    }

    public function bayar($tagihan_id, $method)
    {
        $settingWeb = getSettingWeb();
        $tenantId = resolveTenantIdFromTagihanId((int) $tagihan_id);
        if (! TenantEntitlementService::featureEnabledForTenantId($tenantId, 'payment_gateway', false)) {
            abort(403, 'Payment gateway tidak tersedia untuk tenant ini.');
        }
        $tripay = resolveTripayConfigForTenantId($tenantId);
        if (! $tripay) {
            abort(403, 'Konfigurasi Tripay belum diisi oleh tenant.');
        }
        $tagihans = DB::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->leftJoin('packages', 'pelanggans.paket_layanan', '=', 'packages.id')
            ->select('tagihans.*', 'pelanggans.nama', 'pelanggans.jatuh_tempo', 'pelanggans.email as email_customer', 'pelanggans.no_wa', 'packages.nama_layanan', 'pelanggans.no_layanan')
            ->where('tagihans.id', '=', $tagihan_id)
            ->first();
        $apiKey = $tripay['api_key'];
        $privateKey = $tripay['private_key'];
        $merchantCode = $tripay['merchant_code'];
        $merchantRef = $tagihans->no_tagihan;
        $url = $tripay['base_url'].'transaction/create';
        $amount = $tagihans->total_bayar;
        $data = [
            'method' => $method,
            'merchant_ref' => $merchantRef,
            'amount' => $amount,
            'customer_name' => $tagihans->nama,
            'customer_email' => $tagihans->email_customer,
            'customer_phone' => $tagihans->no_wa,
            'order_items' => [
                [
                    'sku' => 'Internet '.$settingWeb->nama_perusahaan,
                    'name' => 'Pembayaran Internet',
                    'price' => $tagihans->total_bayar,
                    'quantity' => 1,
                    'product_url' => '',
                    'image_url' => '',
                ],
            ],
            'expired_time' => (time() + (24 * 60 * 60)),
            'signature' => hash_hmac('sha256', $merchantCode.$merchantRef.$amount, $privateKey),
        ];
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_FRESH_CONNECT => true,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$apiKey],
            CURLOPT_FAILONERROR => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        ]);
        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);
        $response = json_decode($response)->data;
        if (! empty($response->reference)) {
            DB::table('tagihans')->where('id', (int) $tagihan_id)->update([
                'tripay_reference' => (string) $response->reference,
            ]);
        }

        return redirect()->route('detailBayar', [
            'id' => $response->reference,
        ]);
    }

    public function detailBayar($reference)
    {
        $settingWeb = getSettingWeb();
        $tenantId = resolveTenantIdFromTripayReference((string) $reference);
        if (! TenantEntitlementService::featureEnabledForTenantId($tenantId, 'payment_gateway', false)) {
            abort(403, 'Payment gateway tidak tersedia untuk tenant ini.');
        }
        $tripay = resolveTripayConfigForTenantId($tenantId);
        if (! $tripay) {
            abort(403, 'Konfigurasi Tripay belum diisi oleh tenant.');
        }

        $apiKey = $tripay['api_key'];
        $payload = ['reference' => $reference];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_FRESH_CONNECT => true,
            CURLOPT_URL => $tripay['base_url'].'transaction/detail?'.http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$apiKey],
            CURLOPT_FAILONERROR => false,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($response)->data;

        // 🚨 Cek apakah `pay_code` null → redirect ke checkout_url (e-wallet)
        if (empty($response->pay_code) && ! empty($response->checkout_url)) {
            return redirect()->away($response->checkout_url);
        }

        // 🔁 Kalau bukan e-wallet, lanjut tampilkan detail
        return view('frontend.detailBayar', [
            'detail' => $response,
        ]);
    }

    public function syaratKetentuan()
    {
        return view('frontend.syarat-ketentuan');
    }

    public function daftar(Request $request)
    {
        $ref = trim((string) $request->query('ref', ''));

        return view('frontend.daftar', [
            'ref' => $ref,
        ]);
    }

    public function referralRedirect(string $code)
    {
        $code = trim($code);
        if ($code === '') {
            return redirect()->route('daftar');
        }
        $exists = DB::table('pelanggans')->where('no_layanan', $code)->exists();
        if (! $exists) {
            return redirect()->route('daftar');
        }

        return redirect()->route('daftar', ['ref' => $code]);
    }

    public function daftarStore(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'nik' => 'required|string|max:50',
            'alamat' => 'required|string',
            'no_whatsapp' => 'required|string|max:15',
            'email' => 'required|email|unique:pelanggans,email',
            'kode_referal' => 'nullable|exists:pelanggans,no_layanan',
            'latitude' => 'required|string|max:50',
            'longitude' => 'required|string|max:50',
            'photo_ktp' => 'required|image|max:3024',
        ]);

        DB::beginTransaction();
        $createdId = null;
        $createdNama = null;
        $createdNo = null;
        try {
            $attr = [
                'no_layanan' => $this->generateRequestNoLayanan(),
                'nama' => $validated['nama'],
                'no_ktp' => $validated['nik'],
                'alamat' => $validated['alamat'],
                'no_wa' => $validated['no_whatsapp'],
                'email' => $validated['email'],
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'kode_referal' => $validated['kode_referal'] ?? null,
                'tanggal_daftar' => now()->toDateString(),
                'status_berlangganan' => 'Menunggu',
                'ppn' => 'No',
                'kirim_tagihan_wa' => 'No',
                'auto_isolir' => 'No',
                'password' => Hash::make(Str::random(16)),
            ];

            if ($request->file('photo_ktp') && $request->file('photo_ktp')->isValid()) {
                $path = storage_path('app/public/uploads/photo_ktps/');
                $uploadedFile = $request->file('photo_ktp');
                $filename = $uploadedFile->hashName();

                if (! file_exists($path)) {
                    mkdir($path, 0777, true);
                }
                try {
                    Image::make($uploadedFile->getRealPath())
                        ->resize(500, 500, function ($constraint) {
                            $constraint->upsize();
                            $constraint->aspectRatio();
                        })->save($path.$filename);
                } catch (\Throwable $th) {
                    $uploadedFile->move($path, $filename);
                }

                $attr['photo_ktp'] = $filename;
            }

            $pelanggan = Pelanggan::create($attr);
            $createdId = (int) ($pelanggan->id ?? 0);
            $createdNama = (string) ($pelanggan->nama ?? '');
            $createdNo = (string) ($pelanggan->no_layanan ?? '');

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);

            return redirect()->back()->withInput()->with('error', 'Pendaftaran gagal silahkan coba lagi');
        }

        if ($createdId > 0) {
            AdminController::notifyAdminsByPermission('pelanggan view', 'Request pelanggan baru', 'Request baru: '.($createdNama !== '' ? $createdNama : '-').' ('.($createdNo !== '' ? $createdNo : '-').')', [
                'type' => 'request_pelanggan',
                'badge_key' => 'request_pelanggan',
                'pelanggan_id' => (string) $createdId,
            ]);
        }

        return redirect()->route('daftar')->with('success', 'Pendaftaran berhasil dikirim. Tim kami akan memverifikasi data Anda.');
    }

    private function generateRequestNoLayanan()
    {
        do {
            $candidate = 'RQ'.now()->format('ymd').str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $exists = Pelanggan::where('no_layanan', $candidate)->exists();
        } while ($exists);

        return $candidate;
    }
}
