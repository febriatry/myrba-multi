<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BalanceController extends Controller
{
    public function getHistoricalBalanceByPelanggan(Request $request, $pelangganId)
    {
        // Validasi API Key
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'type' => 'nullable|in:Penambahan,Pengurangan',
            'limit' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return apiResponse(false, 'Validasi gagal', $validator->errors(), 400);
        }

        // Ambil parameter
        $limit = intval($request->input('limit', 10));
        $page = intval($request->input('page', 1));
        $offset = ($page - 1) * $limit;

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $type = $request->input('type');

        // Query dasar
        $query = DB::table('balance_histories')
            ->where('pelanggan_id', $pelangganId);

        // Filter tanggal
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Filter type
        if ($type) {
            $query->where('type', $type);
        }

        // Hitung total data
        $total = $query->count();

        // Ambil data dengan pagination
        $data = $query->orderByDesc('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return apiResponse(true, 'Data history saldo berhasil diambil', [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'data' => $data,
        ]);
    }
}
