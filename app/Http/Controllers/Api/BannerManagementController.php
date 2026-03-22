<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BannerManagement;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BannerManagementController extends Controller
{
    public function getAll(Request $request)
    {
        // Validasi API Key
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        try {
            $banners = BannerManagement::where('is_aktif', 'Yes')
                ->orderBy('urutan', 'asc')
                ->get()
                ->map(function ($row) {
                    return [
                        'id' => $row->id,
                        'file_banner' => asset('storage/uploads/file_banners/' . $row->file_banner),
                        'urutan' => $row->urutan,
                        'is_aktif' => $row->is_aktif,
                    ];
                });

            return apiResponse(true, 'Berhasil mengambil data banner', [
                'banners' => $banners
            ]);
        } catch (ValidationException $e) {
            return apiResponse(false, 'Terjadi kesalahan validasi', ['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return apiResponse(false, 'Terjadi kesalahan pada server: ' . $e->getMessage(), [], 500);
        }
    }
}
