<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ForgotPasswordController extends Controller
{
    /**
     * Meminta token reset password dan mengirimkannya via WhatsApp.
     */
    public function requestToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'no_layanan' => 'required|string|exists:pelanggans,no_layanan',
        ]);

        if ($validator->fails()) {
            return apiResponse(false, 'Nomor layanan tidak ditemukan.', ['errors' => $validator->errors()], 404);
        }

        $pelanggan = Pelanggan::where('no_layanan', $request->no_layanan)->first();

        // Generate token numerik 6 digit
        $token = rand(100000, 999999);

        // Simpan token dan waktu kedaluwarsa (misalnya, 10 menit)
        $pelanggan->reset_token = $token;
        $pelanggan->reset_token_expires_at = Carbon::now()->addMinutes(10);
        $pelanggan->save();

        try {
            $getWaGatewayActive = getWaGatewayActive();
            if ($getWaGatewayActive) {
                $message = "Kode OTP untuk reset password Anda adalah: *" . $token . "*. Kode ini berlaku selama 10 menit. Jangan berikan kode ini kepada siapa pun.";

                // Kirim pesan WhatsApp menggunakan endpoint baru
                $baseNode = env('BASE_NODE', 'http://localhost:3301');
                $endpoint_wa = "$baseNode/api/send-message";

                $response = Http::post($endpoint_wa, [
                    'api_key' => $getWaGatewayActive->api_key,
                    'receiver' => strval($pelanggan->no_wa),
                    'data' => [
                        'message' => $message,
                    ]
                ]);

                // Debug log response WA
                Log::info('WA Gateway Response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Gagal mengirim WA reset password: ' . $e->getMessage());
        }

        return apiResponse(true, 'Token reset password telah dikirim ke nomor WhatsApp Anda.');
    }

    /**
     * Mereset password setelah token diverifikasi.
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'no_layanan' => 'required|string|exists:pelanggans,no_layanan',
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return apiResponse(false, 'Data tidak valid.', ['errors' => $validator->errors()], 422);
        }

        $pelanggan = Pelanggan::where('no_layanan', $request->no_layanan)->first();

        if (!$pelanggan || $pelanggan->reset_token !== $request->token) {
            return apiResponse(false, 'Token tidak valid.', [], 400);
        }

        if (Carbon::now()->isAfter($pelanggan->reset_token_expires_at)) {
            return apiResponse(false, 'Token sudah kedaluwarsa.', [], 400);
        }

        // Update password dan hapus token
        $pelanggan->password = Hash::make($request->password);
        $pelanggan->reset_token = null;
        $pelanggan->reset_token_expires_at = null;
        $pelanggan->save();

        return apiResponse(true, 'Password Anda telah berhasil direset.');
    }
}
