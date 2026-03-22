<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Withdraw;
use App\Models\BalanceHistory;
use App\Models\Pelanggan;

class WithdrawController extends Controller
{
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

        // Query builder
        $query = DB::table('withdraws')
            ->where('pelanggan_id', $pelangganId);

        // Apply filters
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        if ($status && $status !== 'Semua') {
            $query->where('status', $status);
        }

        // Ambil total data
        $total = $query->count();

        // Ambil data withdraw
        $withdraws = $query
            ->orderByDesc('updated_at')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return apiResponse(true, 'Data withdraw berhasil diambil', [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'data' => $withdraws,
        ]);
    }

    public function create(Request $request)
    {
        // Validasi API Key
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        // Validasi input dasar
        $validator = Validator::make($request->all(), [
            'pelanggan_id' => 'required|exists:pelanggans,id',
            'nominal_wd' => 'required|numeric|min:50000', // Minimal penarikan 50.000
            'tanggal_wd' => 'required|date',
        ], [
            'nominal_wd.min' => 'Minimal penarikan adalah Rp 50.000',
        ]);

        if ($validator->fails()) {
            return apiResponse(false, 'Validasi gagal', $validator->errors(), 422);
        }

        // Dapatkan saldo pelanggan
        $pelanggan = DB::table('pelanggans')
            ->select('balance')
            ->where('id', $request->pelanggan_id)
            ->first();

        if (!$pelanggan) {
            return apiResponse(false, 'Pelanggan tidak ditemukan', [], 404);
        }

        autoPayTagihanWithSaldo($request->pelanggan_id);
        $pelanggan = DB::table('pelanggans')
            ->select('balance')
            ->where('id', $request->pelanggan_id)
            ->first();

        // Validasi saldo cukup
        if ($request->nominal_wd > $pelanggan->balance) {
            return apiResponse(false, 'Saldo tidak mencukupi', [
                'balance' => $pelanggan->balance,
                'nominal_wd' => $request->nominal_wd
            ], 422);
        }

        // Buat data withdraw
        $withdrawData = [
            'pelanggan_id' => $request->pelanggan_id,
            'nominal_wd' => $request->nominal_wd,
            'status' => 'Pending',
            'tanggal_wd' => $request->tanggal_wd,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        try {
            // Mulai transaksi database
            DB::beginTransaction();

            $id = DB::table('withdraws')->insertGetId($withdrawData);
            $withdraw = DB::table('withdraws')->find($id);

            DB::commit();

            createTiketAduanForWithdraw((int) $id);
            return apiResponse(true, 'Withdraw berhasil diajukan', $withdraw, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return apiResponse(false, 'Withdraw gagal diajukan', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update an existing withdraw request.
     */
    public function update(Request $request, $id)
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $withdraw = Withdraw::find($id);

        if (!$withdraw) {
            return apiResponse(false, 'Data penarikan tidak ditemukan', [], 404);
        }

        if ($withdraw->status !== 'Pending') {
            return apiResponse(false, 'Hanya permintaan dengan status "Pending" yang bisa diubah', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'nominal_wd' => 'required|numeric|min:10000',
        ]);

        if ($validator->fails()) {
            return apiResponse(false, 'Validasi gagal', ['errors' => $validator->errors()], 422);
        }

        // Pastikan nominal tidak melebihi saldo pelanggan
        autoPayTagihanWithSaldo($withdraw->pelanggan_id);
        $pelanggan = Pelanggan::find($withdraw->pelanggan_id);
        if ($request->nominal_wd > $pelanggan->balance) {
            return apiResponse(false, 'Nominal penarikan tidak boleh melebihi saldo Anda saat ini.', [], 400);
        }

        $withdraw->nominal_wd = $request->nominal_wd;
        $withdraw->save();

        return apiResponse(true, 'Permintaan penarikan berhasil diperbarui', $withdraw);
    }

    /**
     * Delete a withdraw request.
     */
    public function delete(Request $request, $id)
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $withdraw = Withdraw::find($id);

        if (!$withdraw) {
            return apiResponse(false, 'Data penarikan tidak ditemukan', [], 404);
        }

        if ($withdraw->status !== 'Pending') {
            return apiResponse(false, 'Hanya permintaan dengan status "Pending" yang bisa dihapus', [], 403);
        }

        $withdraw->delete();

        return apiResponse(true, 'Permintaan penarikan berhasil dihapus');
    }
}
