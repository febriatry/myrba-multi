<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InformasiManagementController extends Controller
{
    public function getAll(Request $request)
    {
        // Validasi API Key
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        // Ambil parameter limit & page
        $limit = intval($request->input('limit', 10));
        $page = intval($request->input('page', 1));
        $offset = ($page - 1) * $limit;

        // Ambil total data informasi aktif
        $total = DB::table('informasi_management')
            ->where('is_aktif', 'Yes')
            ->count();

        // Ambil data informasi aktif (tanpa filter pelanggan)
        $informasi = DB::table('informasi_management')
            ->where('is_aktif', 'Yes')
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $row->thumbnail = asset('storage/uploads/thumbnails/' . $row->thumbnail);
                return $row;
            });

        return apiResponse(true, 'Data informasi berhasil diambil', [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'data' => $informasi,
        ]);
    }

    public function getById(Request $request, $id)
    {
        // Validasi API Key
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $informasi = DB::table('informasi_management')
            ->where('id', $id)
            ->where('is_aktif', 'Yes')
            ->first();

        if ($informasi) {
            $informasi->thumbnail = asset('storage/uploads/thumbnails/' . $informasi->thumbnail);
        }

        if (!$informasi) {
            return apiResponse(false, 'Informasi tidak ditemukan', [], 404);
        }

        return apiResponse(true, 'Detail informasi ditemukan', ['informasi' => $informasi]);
    }

    public function searchByJudul(Request $request)
    {
        // Validasi API Key
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $keyword = $request->input('q', '');
        $limit = intval($request->input('limit', 10));
        $page = intval($request->input('page', 1));
        $offset = ($page - 1) * $limit;

        // Query pencarian by judul
        $query = DB::table('informasi_management')
            ->where('is_aktif', 'Yes')
            ->where('judul', 'like', '%' . $keyword . '%');

        $total = $query->count();

        $results = $query
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $row->thumbnail = asset('storage/uploads/thumbnails/' . $row->thumbnail);
                return $row;
            });

        return apiResponse(true, 'Hasil pencarian berhasil diambil', [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'data' => $results,
        ]);
    }
}
