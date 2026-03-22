<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SettingWeb;
use Illuminate\Support\Facades\Http;
use \RouterOS\Query;
use \RouterOS\Client;
use \RouterOS\Exceptions\ConnectException;


class TagihanController extends Controller
{
    public function getById(Request $request, $id)
    {
        // Validasi API Key
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $tagihan = DB::table('tagihans')->where('id', $id)->first();
        if (!$tagihan) {
            return apiResponse(false, 'Tagihan tidak ditemukan', [], 404);
        }

        return apiResponse(true, 'Detail tagihan ditemukan', ['tagihan' => $tagihan]);
    }

    public function getByPelanggan(Request $request, $pelangganId)
    {
        // Validasi API Key
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        // Ambil parameter limit & page, default: 10 per halaman
        $limit = intval($request->input('limit', 10));
        $page = intval($request->input('page', 1));
        $offset = ($page - 1) * $limit;

        // Filter parameters
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $status = $request->input('status');
        $metodeBayar = $request->input('metode_bayar');

        // Query builder
        $query = DB::table('tagihans')
            ->where('pelanggan_id', $pelangganId);

        // Apply filters
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        if ($status && $status !== 'Semua') {
            $query->where('status_bayar', $status);
        }
        if ($metodeBayar && $metodeBayar !== 'Semua') {
            $query->where('metode_bayar', $metodeBayar);
        }

        // Ambil total data
        $total = $query->count();

        // Ambil data tagihan
        $tagihans = $query
            ->orderByDesc('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return apiResponse(true, 'Data tagihan berhasil diambil', [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'data' => $tagihans,
        ]);
    }

    public function getPaymentMethods(Request $request)
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        try {
            $settingWeb = SettingWeb::first();
            $url = $settingWeb->url_tripay . 'merchant/payment-channel';
            $api_key = $settingWeb->api_key_tripay;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $api_key
            ])->get($url);

            $result = json_decode($response->getBody());

            if ($result->success == true) {
                return apiResponse(true, 'Metode pembayaran berhasil diambil', [
                    'payment_methods' => $result->data
                ]);
            } else {
                return apiResponse(false, $result->message ?? 'Gagal mengambil metode pembayaran', [], 400);
            }
        } catch (\Exception $e) {
            return apiResponse(false, 'Terjadi kesalahan: ' . $e->getMessage(), [], 500);
        }
    }

    public function payWithSaldo(Request $request, $tagihanId)
    {
        // Validate API Key
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        // Start database transaction
        DB::beginTransaction();

        try {
            // Get the invoice
            $tagihan = DB::table('tagihans')
                ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
                ->leftJoin('packages', 'pelanggans.paket_layanan', '=', 'packages.id')
                ->where('tagihans.id', $tagihanId)
                ->where('tagihans.status_bayar', '=', 'Belum Bayar')
                ->select(
                    'tagihans.*',
                    'tagihans.id as tagihan_id',
                    'tagihans.total_bayar as nominal',
                    'pelanggans.id as pelanggan_id',
                    'pelanggans.nama as nama_pelanggan',
                    'pelanggans.router',
                    'pelanggans.jatuh_tempo',
                    'pelanggans.email as email_customer',
                    'pelanggans.no_wa',
                    'pelanggans.balance',
                    'pelanggans.status_berlangganan',
                    'pelanggans.mode_user',
                    'pelanggans.user_pppoe',
                    'pelanggans.user_static',
                    'packages.nama_layanan',
                    'packages.profile',
                    'pelanggans.no_layanan'
                )
                ->first();

            if (!$tagihan) {
                return apiResponse(false, 'Tagihan tidak ditemukan atau sudah dibayar', [], 404);
            }

            // Check if customer has sufficient balance
            if ($tagihan->balance < $tagihan->total_bayar) {
                return apiResponse(false, 'Saldo tidak mencukupi untuk membayar tagihan ini', [], 400);
            }

            // Update invoice status
            DB::table('tagihans')
                ->where('id', $tagihanId)
                ->update([
                    'status_bayar' => 'Sudah Bayar',
                    'metode_bayar' => 'Saldo',
                    'tanggal_bayar' => now(),
                    'tanggal_kirim_notif_wa' => now()
                ]);

            // Insert income record
            $categoryId = getSaldoIncomeCategoryId();
            DB::table('pemasukans')->insert([
                'nominal' => $tagihan->total_bayar,
                'tanggal' => now(),
                'category_pemasukan_id' => $categoryId,
                'referense_id' =>$tagihanId,
                'metode_bayar' => 'Saldo',
                'keterangan' => 'Pembayaran Tagihan no Tagihan ' . $tagihan->no_tagihan . ' a/n ' . $tagihan->nama_pelanggan . ' Periode ' . $tagihan->periode,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Deduct customer balance
            $newBalance = $tagihan->balance - $tagihan->total_bayar;
            DB::table('pelanggans')
                ->where('id', $tagihan->pelanggan_id)
                ->update(['balance' => $newBalance]);

            // Record balance history
            DB::table('balance_histories')->insert([
                'pelanggan_id' => $tagihan->pelanggan_id,
                'type' => 'Pengurangan',
                'amount' => $tagihan->total_bayar,
                'balance_before' => $tagihan->balance,
                'balance_after' => $newBalance,
                'description' => 'Pembayaran Tagihan #' . $tagihan->no_tagihan,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            applyInvestorSharingForPaidTagihan((int) $tagihanId);

            // Handle isolation status if no other unpaid invoices
            $unpaidCount = DB::table('tagihans')
                ->where('pelanggan_id', $tagihan->pelanggan_id)
                ->where('status_bayar', 'Belum Bayar')
                ->count();

            if ($unpaidCount < 1) {
                // Update customer status to active
                DB::table('pelanggans')
                    ->where('id', $tagihan->pelanggan_id)
                    ->update(['status_berlangganan' => 'Aktif']);

                // Handle Mikrotik isolation removal
                $client = self::setRoute($tagihan->router);
                if ($tagihan->mode_user == 'PPOE') {
                    $queryGet = (new Query('/ppp/secret/print'))
                        ->where('name', $tagihan->user_pppoe);
                    $data = $client->query($queryGet)->read();

                    if (!empty($data)) {
                        $idSecret = $data[0]['.id'];
                        $existingComment = $data[0]['comment'] ?? null;
                        $comment = myrbaMergeMikrotikComment($existingComment, 'Isolir terbuka otomatis (lunas)');
                        $queryComment = (new Query('/ppp/secret/set'))
                            ->equal('.id', $idSecret)
                            ->equal('profile', $tagihan->profile)
                            ->equal('comment', $comment);
                        $client->query($queryComment)->read();
                        $client->query((new Query('/ppp/secret/enable'))->equal('.id', $idSecret))->read();

                        // Remove active session
                        $queryGet = (new Query('/ppp/active/print'))
                            ->where('name', $tagihan->user_pppoe);
                        $data = $client->query($queryGet)->read();
                        if (!empty($data)) {
                            $idActive = $data[0]['.id'];
                            $queryDelete = (new Query('/ppp/active/remove'))
                                ->equal('.id', $idActive);
                            $client->query($queryDelete)->read();
                        }
                    }
                } else {
                    $queryGet = (new Query('/queue/simple/print'))
                        ->where('name', $tagihan->user_static);
                    $data = $client->query($queryGet)->read();

                    if (!empty($data) && isset($data[0]['target'])) {
                        $ip = $data[0]['target'];
                        $parts = explode('/', $ip);
                        $fixIp = $parts[0];

                        $queryGet = (new Query('/ip/firewall/address-list/print'))
                            ->where('list', 'expired')
                            ->where('address', $fixIp);
                        $data = $client->query($queryGet)->read();

                        if (!empty($data) && isset($data[0]['.id'])) {
                            $idIP = $data[0]['.id'];
                            $queryRemove = (new Query('/ip/firewall/address-list/remove'))
                                ->equal('.id', $idIP);
                            $client->query($queryRemove)->read();
                        }
                    }
                }
            }

            // Commit transaction
            DB::commit();
            // Send WhatsApp notification if active gateway exists
            $waGateway = getWaGatewayActive();
            if ($waGateway && $waGateway->is_aktif === 'Yes' && $waGateway->is_wa_payment_active === 'Yes') {
                sendNotifWa(
                    $waGateway->api_key,
                    $tagihan,
                    'payment_receipt',
                    $tagihan->no_wa
                );
            }

            return apiResponse(true, 'Pembayaran dengan saldo berhasil', [
                'tagihan_id' => $tagihanId,
                'saldo_sebelumnya' => $tagihan->balance,
                'saldo_sekarang' => $newBalance,
                'nominal_tagihan' => $tagihan->total_bayar
            ]);
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            return apiResponse(false, 'Terjadi kesalahan: ' . $e->getMessage(), [], 500);
        }
    }

    function setRoute($id)
    {
        $router = DB::table('settingmikrotiks')->where('id', $id)->first();
        if ($router) {
            try {
                return new Client([
                    'host' => $router->host,
                    'user' => $router->username,
                    'pass' => $router->password,
                    'port' => (int) $router->port,
                ]);
            } catch (ConnectException $e) {
                echo $e->getMessage() . PHP_EOL;
                die();
            }
        }
    }

    public function payWithMethod(Request $request, $tagihanId)
    {
        // Validate API Key
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        // Validate method code exists in request
        $methodCode = $request->input('method_code');
        if (!$methodCode) {
            return apiResponse(false, 'Metode pembayaran harus dipilih', [], 400);
        }

        // Get invoice data
        $tagihan = DB::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->select(
                'tagihans.*',
                'tagihans.id as tagihan_id',
                'pelanggans.nama',
                'pelanggans.email as email_customer',
                'pelanggans.no_wa',
                'pelanggans.no_layanan'
            )
            ->where('tagihans.id', $tagihanId)
            ->where('tagihans.status_bayar', 'Belum Bayar')
            ->first();

        if (!$tagihan) {
            return apiResponse(false, 'Tagihan tidak ditemukan atau sudah dibayar', [], 404);
        }

        // Get Tripay settings
        $settingWeb = SettingWeb::first();
        $apiKey = $settingWeb->api_key_tripay;
        $privateKey = $settingWeb->private_key;
        $merchantCode = $settingWeb->kode_merchant;
        $merchantRef = $tagihan->no_tagihan;
        $amount = $tagihan->total_bayar;

        // Prepare data for Tripay
        $data = [
            'method'         => $methodCode,
            'merchant_ref'   => $merchantRef,
            'amount'         => $amount,
            'customer_name'  => $tagihan->nama,
            'customer_email' => $tagihan->email_customer,
            'customer_phone' => $tagihan->no_wa,
            'order_items'    => [
                [
                    'sku'         => 'Internet-' . $tagihan->no_layanan,
                    'name'        => 'Pembayaran Internet',
                    'price'       => $amount,
                    'quantity'    => 1,
                    'product_url' => '',
                    'image_url'   => '',
                ]
            ],
            'expired_time' => (time() + (24 * 60 * 60)), // 24 hours expiry
            'signature'    => hash_hmac('sha256', $merchantCode . $merchantRef . $amount, $privateKey)
        ];

        try {
            // Create transaction in Tripay
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey
            ])->post($settingWeb->url_tripay . 'transaction/create', $data);

            $result = $response->json();

            if (!$result['success']) {
                return apiResponse(false, $result['message'] ?? 'Gagal membuat transaksi pembayaran', [], 400);
            }

            $transaction = $result['data'];

            // For e-wallets, just return the checkout URL
            if (empty($transaction['pay_code']) && !empty($transaction['checkout_url'])) {
                return apiResponse(true, 'Silakan selesaikan pembayaran', [
                    'payment_type' => 'ewallet',
                    'checkout_url' => $transaction['checkout_url'],
                    'payment_url' => $transaction['payment_url'] ?? null,
                    'reference' => $transaction['reference']
                ]);
            }

            // For non-ewallets, get full payment instructions
            $detailResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey
            ])->get($settingWeb->url_tripay . 'transaction/detail', [
                'reference' => $transaction['reference']
            ]);

            $detailResult = $detailResponse->json();

            if (!$detailResult['success']) {
                return apiResponse(false, $detailResult['message'] ?? 'Gagal mendapatkan detail pembayaran', [], 400);
            }

            return apiResponse(true, 'Instruksi pembayaran berhasil didapatkan', [
                'payment_type' => 'virtual_account',
                'payment_data' => $detailResult['data']
            ]);
        } catch (\Exception $e) {
            return apiResponse(false, 'Terjadi kesalahan: ' . $e->getMessage(), [], 500);
        }
    }

    public function search(Request $request)
    {
        // Validasi API Key
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $pelangganId = $request->input('pelanggan_id');
        $query = $request->input('query');
        $limit = $request->input('limit', 5);

        if (!$pelangganId || !$query) {
            return apiResponse(false, 'Parameter pencarian tidak valid', [], 400);
        }

        $tagihans = DB::table('tagihans')
            ->where('pelanggan_id', $pelangganId)
            ->where('no_tagihan', 'like', "%{$query}%")
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return apiResponse(true, 'Hasil pencarian tagihan', ['data' => $tagihans]);
    }
}
