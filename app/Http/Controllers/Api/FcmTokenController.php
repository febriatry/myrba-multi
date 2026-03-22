<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FcmTokenController extends Controller
{
    public function store(Request $request)
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string|max:4096',
            'pelanggan_id' => 'nullable|integer',
            'no_layanan' => 'nullable|string|max:50',
            'platform' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return apiResponse(false, $validator->errors()->first(), [
                'errors' => $validator->errors(),
            ], 422);
        }

        $pelangganId = $request->input('pelanggan_id');
        $noLayanan = trim((string) $request->input('no_layanan', ''));
        $token = trim((string) $request->input('fcm_token', ''));
        $platform = trim((string) $request->input('platform', 'android'));

        if (empty($pelangganId) && $noLayanan === '') {
            return apiResponse(false, 'pelanggan_id atau no_layanan wajib diisi', [], 422);
        }

        if (!empty($pelangganId)) {
            $pelanggan = DB::table('pelanggans')
                ->select('id', 'no_layanan')
                ->where('id', (int) $pelangganId)
                ->first();
            if (!$pelanggan) {
                return apiResponse(false, 'Pelanggan tidak ditemukan', [], 404);
            }
            $pelangganId = (int) $pelanggan->id;
            $noLayanan = $noLayanan !== '' ? $noLayanan : (string) ($pelanggan->no_layanan ?? '');
        } else {
            $pelanggan = DB::table('pelanggans')
                ->select('id', 'no_layanan')
                ->where('no_layanan', $noLayanan)
                ->first();
            if (!$pelanggan) {
                return apiResponse(false, 'Pelanggan tidak ditemukan', [], 404);
            }
            $pelangganId = (int) $pelanggan->id;
            $noLayanan = (string) ($pelanggan->no_layanan ?? $noLayanan);
        }

        $existing = DB::table('pelanggan_fcm_tokens')
            ->where('token', $token)
            ->first();

        if ($existing) {
            DB::table('pelanggan_fcm_tokens')
                ->where('id', $existing->id)
                ->update([
                    'pelanggan_id' => $pelangganId,
                    'no_layanan' => $noLayanan !== '' ? $noLayanan : null,
                    'platform' => $platform !== '' ? $platform : 'android',
                    'last_seen_at' => now(),
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('pelanggan_fcm_tokens')->insert([
                'pelanggan_id' => $pelangganId,
                'no_layanan' => $noLayanan !== '' ? $noLayanan : null,
                'token' => $token,
                'platform' => $platform !== '' ? $platform : 'android',
                'last_seen_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return apiResponse(true, 'FCM token berhasil disimpan', [
            'pelanggan_id' => $pelangganId,
            'no_layanan' => $noLayanan,
        ]);
    }
}

