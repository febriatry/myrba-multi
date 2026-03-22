<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SettingWeb;

class SettingWebController extends Controller
{
    /**
     * Mengambil data publik dari pengaturan website.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPublicSettings(Request $request)
    {
        // 1. Validasi API Key menggunakan helper yang sudah ada
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        // 2. Ambil data dari database, pilih hanya kolom yang dibutuhkan
        $settings = SettingWeb::select(
            'nama_perusahaan',
            'email',
            'alamat',
            'logo',
            'telepon_perusahaan',
            'no_wa',
            'deskripsi_perusahaan'
        )->first();

        // 3. Handle jika data tidak ditemukan
        if (!$settings) {
            return apiResponse(false, 'Pengaturan website tidak ditemukan.', null, 404);
        }

        // 4. Buat URL lengkap untuk logo jika ada
        if ($settings->logo) {
            $settings->logo = asset('storage/uploads/logos/' . $settings->logo);
        }

        // 5. Kembalikan response sukses dalam format JSON
        return apiResponse(true, 'Data pengaturan website berhasil diambil.', $settings);
    }
}
