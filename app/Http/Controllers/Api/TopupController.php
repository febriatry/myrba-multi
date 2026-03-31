<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use App\Models\Topup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TopupController extends Controller
{
    /**
     * Get topup history for a customer.
     */
    public function getHistory(Request $request, $pelangganId)
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $limit = intval($request->input('limit', 10));
        $page = intval($request->input('page', 1));
        $offset = ($page - 1) * $limit;

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $status = $request->input('status');
        $metode = $request->input('metode');

        $query = DB::table('topups')->where('pelanggan_id', $pelangganId);

        if ($startDate) {
            $query->whereDate('tanggal_topup', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('tanggal_topup', '<=', $endDate);
        }
        if ($status && $status !== 'Semua') {
            $query->where('status', strtolower($status));
        }
        if ($metode && $metode !== 'Semua') {
            $query->where('metode', strtolower($metode));
        }

        $total = $query->count();
        $data = $query->orderByDesc('tanggal_topup')->offset($offset)->limit($limit)->get();

        return apiResponse(true, 'Data riwayat top-up berhasil diambil', [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'data' => $data,
        ]);
    }

    /**
     * Create a manual topup request.
     */
    public function createManual(Request $request)
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        return apiResponse(false, 'Top up manual dinonaktifkan. Silakan gunakan top up via Tripay.', [], 403);
    }

    /**
     * Create an automatic (Tripay) topup request.
     */
    public function createTripay(Request $request)
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $validator = Validator::make($request->all(), [
            'pelanggan_id' => 'required|exists:pelanggans,id',
            'nominal' => 'required|numeric|min:10000',
            'method_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return apiResponse(false, 'Validasi gagal', ['errors' => $validator->errors()], 422);
        }

        $pelanggan = Pelanggan::find($request->pelanggan_id);
        $tenantId = (int) ($pelanggan->tenant_id ?? 0);
        $tripay = resolveTripayConfigForTenantId($tenantId);
        if (! $tripay) {
            return apiResponse(false, 'Tripay belum dikonfigurasi untuk tenant ini.', [], 400);
        }

        $apiKey = $tripay['api_key'];
        $privateKey = $tripay['private_key'];
        $merchantCode = $tripay['merchant_code'];
        $merchantRef = 'TOPUP-'.strtoupper(Str::random(10));
        $amount = $request->nominal;

        $data = [
            'method' => $request->method_code,
            'merchant_ref' => $merchantRef,
            'amount' => $amount,
            'customer_name' => $pelanggan->nama,
            'customer_email' => $pelanggan->email,
            'customer_phone' => $pelanggan->no_wa,
            'order_items' => [
                [
                    'sku' => 'TOPUP-SALDO',
                    'name' => 'Top Up Saldo',
                    'price' => $amount,
                    'quantity' => 1,
                ],
            ],
            'expired_time' => (time() + (24 * 60 * 60)), // 24 hours
            'signature' => hash_hmac('sha256', $merchantCode.$merchantRef.$amount, $privateKey),
        ];

        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer '.$apiKey])
                ->post($tripay['base_url'].'transaction/create', $data);

            $result = $response->json();

            if (! $result['success']) {
                return apiResponse(false, $result['message'] ?? 'Gagal membuat transaksi Tripay', [], 400);
            }

            $tripayRef = is_array($result['data'] ?? null) ? ($result['data']['reference'] ?? null) : null;

            // Simpan data topup ke database dengan status pending
            DB::table('topups')->insert([
                'tenant_id' => $tenantId,
                'no_topup' => $merchantRef,
                'pelanggan_id' => $request->pelanggan_id,
                'tanggal_topup' => now(),
                'nominal' => $amount,
                'status' => 'pending',
                'metode' => 'tripay',
                'metode_topup' => $request->method_code,
                'payload_tripay' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return apiResponse(true, 'Transaksi Tripay berhasil dibuat.', $result['data']);
        } catch (\Exception $e) {
            return apiResponse(false, 'Gagal terhubung ke payment gateway: '.$e->getMessage(), [], 500);
        }
    }

    /**
     * Update a manual topup request.
     */
    public function updateManual(Request $request, $id)
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        return apiResponse(false, 'Top up manual dinonaktifkan. Silakan gunakan top up via Tripay.', [], 403);
    }

    /**
     * Delete a manual topup request.
     */
    public function deleteManual(Request $request, $id)
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        return apiResponse(false, 'Top up manual dinonaktifkan. Silakan gunakan top up via Tripay.', [], 403);
    }

    /**
     * Hapus data top up dari database.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Topup $topup)
    {
        // Otorisasi: Pastikan user punya permission untuk menghapus
        $this->authorize('topup delete');

        // Validasi: Hanya hapus jika statusnya 'pending'
        if ($topup->status !== 'pending') {
            return redirect()->route('topups.index')->with('error', 'Gagal! Hanya top up dengan status pending yang bisa dihapus.');
        }

        // Jika top up manual, hapus juga bukti transfernya
        if ($topup->metode == 'manual' && $topup->bukti_topup) {
            Storage::delete('public/uploads/bukti_topup/'.$topup->bukti_topup);
        }

        // Hapus data dari database
        $topup->delete();

        return redirect()->route('topups.index')->with('success', 'Data top up berhasil dihapus.');
    }
}
