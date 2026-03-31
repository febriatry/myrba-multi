<?php

namespace App\Http\Controllers;

use App\Models\BalanceHistory;
use App\Models\Pelanggan;
use App\Models\Pemasukan;
use App\Models\Tagihan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use RouterOS\Client;
use RouterOS\Exceptions\ConnectException;
use RouterOS\Query;

class TripayCallbackController extends Controller
{
    public function handle(Request $request)
    {
        $callbackSignature = $request->server('HTTP_X_CALLBACK_SIGNATURE');
        $json = $request->getContent();

        if ((string) $request->server('HTTP_X_CALLBACK_EVENT') !== 'payment_status') {
            return Response::json([
                'success' => false,
                'message' => 'Unrecognized callback event, no action was taken',
            ]);
        }

        $data = json_decode($json);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return Response::json([
                'success' => false,
                'message' => 'Invalid data sent by Tripay',
            ]);
        }

        if ($data->is_closed_payment !== 1) {
            return Response::json(['success' => true]); // Bukan pembayaran tertutup, lewati
        }

        $invoiceId = $data->merchant_ref;
        $status = strtoupper((string) $data->status);
        $settingWeb = DB::table('setting_web')->first();
        $tenantId = 1;
        if (Str::startsWith($invoiceId, 'INV-')) {
            $tenantId = resolveTenantIdFromInvoiceRef((string) $invoiceId);
        } elseif (Str::startsWith($invoiceId, 'TOPUP-')) {
            $tenantId = resolveTenantIdFromTopupRef((string) $invoiceId);
        }
        $tripay = resolveTripayConfigForTenantId((int) $tenantId);

        $privateKey = $tripay ? $tripay['private_key'] : ($settingWeb->private_key ?? '');
        $signature = hash_hmac('sha256', $json, (string) $privateKey);
        if ($signature !== (string) $callbackSignature) {
            return Response::json([
                'success' => false,
                'message' => 'Invalid signature',
            ]);
        }

        if (Str::startsWith($invoiceId, 'INV-')) {
            // ====== PEMBAYARAN TAGIHAN ======
            $invoice = DB::table('tagihans')
                ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
                ->leftJoin('packages', 'pelanggans.paket_layanan', '=', 'packages.id')
                ->where('tagihans.no_tagihan', $invoiceId)
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
                    'packages.nama_layanan',
                    'pelanggans.no_layanan'
                )
                ->first();

            if (! $invoice) {
                return Response::json([
                    'success' => false,
                    'message' => 'No invoice found or already paid: '.$invoiceId,
                ]);
            }

            switch ($status) {
                case 'PAID':
                    DB::table('tagihans')
                        ->where('no_tagihan', $invoiceId)
                        ->update([
                            'status_bayar' => 'Sudah Bayar',
                            'payload_tripay' => $json,
                            'metode_bayar' => 'Payment Tripay',
                            'tanggal_bayar' => now(),
                            'tanggal_kirim_notif_wa' => now(),
                        ]);
                    break;

                case 'EXPIRED':
                case 'FAILED':
                    DB::table('tagihans')
                        ->where('no_tagihan', $invoiceId)
                        ->update([
                            'status_bayar' => 'Belum Bayar',
                            'payload_tripay' => $json,
                        ]);
                    break;

                default:
                    return Response::json([
                        'success' => false,
                        'message' => 'Unrecognized payment status',
                    ]);
            }

            if ($status == 'PAID') {
                $categoryId = getInternetIncomeCategoryIdForPelanggan($invoice->pelanggan_id);
                DB::table('pemasukans')->insert([
                    'tenant_id' => (int) $tenantId,
                    'nominal' => $invoice->total_bayar,
                    'tanggal' => now(),
                    'category_pemasukan_id' => $categoryId,
                    'referense_id' => $invoice->tagihan_id,
                    'metode_bayar' => 'Payment Tripay',
                    'keterangan' => 'Pembayaran Tagihan no Tagihan '.$invoice->no_tagihan.' a/n '.$invoice->nama_pelanggan.' Periode '.$invoice->periode,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                applyInvestorSharingForPaidTagihan((int) $invoice->tagihan_id);

                $waGateway = getWaGatewayActive();
                if ($waGateway && $waGateway->is_aktif === 'Yes' && $waGateway->is_wa_payment_active === 'Yes') {
                    sendNotifWa(
                        $waGateway->api_key,
                        $invoice,
                        'payment_receipt',
                        $invoice->no_wa
                    );
                }

                $cekTagihan = Tagihan::where('pelanggan_id', $invoice->pelanggan_id)
                    ->where('status_bayar', 'Belum Bayar')
                    ->count();

                if ($cekTagihan < 1) {
                    $client = self::setRoute($invoice->router);
                    $pelanggan = DB::table('pelanggans')
                        ->leftJoin('packages', 'pelanggans.paket_layanan', '=', 'packages.id')
                        ->select(
                            'packages.profile',
                            'pelanggans.mode_user',
                            'pelanggans.user_pppoe',
                            'pelanggans.user_static'
                        )
                        ->where('pelanggans.id', $invoice->pelanggan_id)
                        ->first();

                    if ($pelanggan->mode_user == 'PPOE') {
                        $queryGet = (new Query('/ppp/secret/print'))
                            ->where('name', $pelanggan->user_pppoe);
                        $data = $client->query($queryGet)->read();
                        if (! empty($data) && isset($data[0]['.id'])) {
                            $idSecret = $data[0]['.id'];
                            $existingComment = $data[0]['comment'] ?? null;
                            $comment = myrbaMergeMikrotikComment($existingComment, 'Isolir terbuka otomatis (lunas)');
                            $queryComment = (new Query('/ppp/secret/set'))
                                ->equal('.id', $idSecret)
                                ->equal('profile', $pelanggan->profile)
                                ->equal('comment', $comment);
                            $client->query($queryComment)->read();
                            $client->query((new Query('/ppp/secret/enable'))->equal('.id', $idSecret))->read();
                        }

                        $queryGet = (new Query('/ppp/active/print'))
                            ->where('name', $pelanggan->user_pppoe);
                        $data = $client->query($queryGet)->read();
                        if (! empty($data) && isset($data[0]['.id'])) {
                            $idActive = $data[0]['.id'];
                            $queryDelete = (new Query('/ppp/active/remove'))
                                ->equal('.id', $idActive);
                            $client->query($queryDelete)->read();
                        }
                    } else {
                        $queryGet = (new Query('/queue/simple/print'))
                            ->where('name', $pelanggan->user_static);
                        $data = $client->query($queryGet)->read();
                        $ip = explode('/', $data[0]['target'])[0];

                        $queryGet = (new Query('/ip/firewall/address-list/print'))
                            ->where('list', 'expired')
                            ->where('address', $ip);
                        $data = $client->query($queryGet)->read();

                        if (isset($data[0]['.id'])) {
                            $queryRemove = (new Query('/ip/firewall/address-list/remove'))
                                ->equal('.id', $data[0]['.id']);
                            $client->query($queryRemove)->read();
                        }
                    }

                    DB::table('pelanggans')
                        ->where('id', $invoice->pelanggan_id)
                        ->update(['status_berlangganan' => 'Aktif']);
                }
            }

            if ($tripay && ($tripay['gateway_mode'] ?? 'owner') === 'owner') {
                recordTripayUsageLog((int) $tenantId, (string) $invoiceId, [
                    'gateway_mode' => 'owner',
                    'type' => 'tagihan',
                    'status' => $status,
                    'amount' => (int) ($invoice->nominal ?? $invoice->total_bayar ?? 0),
                    'method' => $data->payment_method ?? ($data->payment_method_code ?? null),
                    'tripay_reference' => $data->reference ?? null,
                    'paid_at' => $status === 'PAID' ? now() : null,
                    'payload' => json_decode($json, true),
                ]);
            }
        } elseif (Str::startsWith($invoiceId, 'TOPUP-')) {
            // ====== TOP-UP SALDO ======
            $topup = DB::table('topups')
                ->where('no_topup', $invoiceId)
                ->where('status', 'pending')
                ->first();

            if (! $topup) {
                return Response::json([
                    'success' => false,
                    'message' => 'No topup found or already processed: '.$invoiceId,
                ]);
            }

            if ($status === 'PAID') {
                DB::beginTransaction();
                try {
                    DB::table('topups')->where('id', $topup->id)->update([
                        'status' => 'success',
                        'payload_tripay' => $json,
                        'tanggal_callback_tripay' => now(),
                    ]);

                    $pelanggan = Pelanggan::find($topup->pelanggan_id);
                    $balanceBefore = $pelanggan->balance;
                    $balanceAfter = $balanceBefore + $topup->nominal;
                    $pelanggan->update(['balance' => $balanceAfter]);

                    BalanceHistory::create([
                        'pelanggan_id' => $pelanggan->id,
                        'type' => 'Penambahan',
                        'amount' => $topup->nominal,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                        'description' => "Top-up saldo via Tripay ({$topup->metode_topup}) sebesar ".rupiah($topup->nominal),
                    ]);

                    Pemasukan::create([
                        'tenant_id' => (int) $tenantId,
                        'nominal' => $topup->nominal,
                        'tanggal' => now(),
                        'keterangan' => "Topup pelanggan {$pelanggan->nama} via Tripay tanggal ".now()->format('d-m-Y').' sebesar '.rupiah($topup->nominal),
                        'category_pemasukan_id' => 2,
                        'metode_bayar' => 'Payment Tripay',
                    ]);

                    autoPayTagihanWithSaldo($pelanggan->id);

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Gagal memproses callback topup: '.$e->getMessage());
                }
            } else {
                DB::table('topups')->where('id', $topup->id)->update([
                    'status' => strtolower($status),
                    'payload_tripay' => $json,
                ]);
            }

            if ($tripay && ($tripay['gateway_mode'] ?? 'owner') === 'owner') {
                recordTripayUsageLog((int) $tenantId, (string) $invoiceId, [
                    'gateway_mode' => 'owner',
                    'type' => 'topup',
                    'status' => $status,
                    'amount' => (int) ($topup->nominal ?? 0),
                    'method' => $data->payment_method ?? ($data->payment_method_code ?? null),
                    'tripay_reference' => $data->reference ?? null,
                    'paid_at' => $status === 'PAID' ? now() : null,
                    'payload' => json_decode($json, true),
                ]);
            }
        }

        return Response::json(['success' => true]);
    }

    public static function setRoute($id)
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
                echo $e->getMessage().PHP_EOL;
                exit();
            }
        }
    }
}
