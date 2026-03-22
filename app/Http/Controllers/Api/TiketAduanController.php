<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Image;

class TiketAduanController extends Controller
{
    public function create(Request $request)
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $validator = Validator::make($request->all(), [
            'pelanggan_id'     => 'required|exists:pelanggans,id',
            'deskripsi_aduan'  => 'required|string',
            'status'           => 'required|in:Menunggu,Diproses,Selesai,Dibatalkan',
            'prioritas'        => 'required|in:Rendah,Sedang,Tinggi',
            'lampiran'         => 'nullable|file|mimes:jpg,jpeg,png,pdf,mp4,mov,avi,webm|max:20480',
        ]);

        if ($validator->fails()) {
            return apiResponse(false, 'Validasi gagal', ['errors' => $validator->errors()], 422);
        }

        $lampiranPath = null;
        if ($request->hasFile('lampiran')) {
            $file = $request->file('lampiran');
            $filename = $file->hashName();
            $path = storage_path('app/public/uploads/lampirans/');

            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            if (in_array($file->extension(), ['jpg', 'jpeg', 'png'])) {
                Image::make($file->getRealPath())
                    ->resize(500, 500, function ($constraint) {
                        $constraint->upsize();
                        $constraint->aspectRatio();
                    })
                    ->save($path . $filename);
            } else {
                $file->move($path, $filename);
            }

            // Simpan hanya nama file-nya ke DB
            $lampiranPath = $filename;
        }

        $id = DB::table('tiket_aduans')->insertGetId([
            'nomor_tiket'     => 'TKT-' . strtoupper(Str::random(6)),
            'pelanggan_id'    => $request->pelanggan_id,
            'deskripsi_aduan' => $request->deskripsi_aduan,
            'tanggal_aduan'   => date('Y-m-d H:i:s'),
            'status'          => $request->status,
            'prioritas'       => $request->prioritas,
            'lampiran'        => $lampiranPath,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        if (($request->status ?? '') === 'Menunggu') {
            $pelanggan = DB::table('pelanggans')->where('id', (int) $request->pelanggan_id)->select('nama', 'no_layanan')->first();
            $title = 'Tiket baru';
            $body = 'Tiket masuk dari ' . (($pelanggan->nama ?? '-') . ' (' . ($pelanggan->no_layanan ?? '-') . ')');
            AdminController::notifyAdminsByPermission('tiket aduan view', $title, $body, [
                'type' => 'tiket',
                'badge_key' => 'daftar_tiket',
                'tiket_id' => (string) $id,
            ]);
        }

        return apiResponse(true, 'Tiket berhasil dibuat', ['id' => $id]);
    }

    public function update(Request $request, $id)
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $tiket = DB::table('tiket_aduans')->where('id', $id)->first();
        if (!$tiket) {
            return apiResponse(false, 'Tiket tidak ditemukan', [], 404);
        }

        $validator = Validator::make($request->all(), [
            'deskripsi_aduan' => 'sometimes|required|string',
            'prioritas'       => 'sometimes|required|in:Rendah,Sedang,Tinggi',
            'lampiran'        => 'nullable|file|mimes:jpg,jpeg,png,pdf,mp4,mov,avi,webm|max:20480',
        ]);

        if ($validator->fails()) {
            return apiResponse(false, 'Validasi gagal', ['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['deskripsi_aduan', 'prioritas']);
        $data['updated_at'] = now();

        if ($request->hasFile('lampiran')) {
            // Hapus file lama (jika ada)
            if ($tiket->lampiran) {
                $oldPath = storage_path('app/public/uploads/lampirans/' . $tiket->lampiran);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            // Proses file baru
            $file = $request->file('lampiran');
            $filename = $file->hashName();
            $path = storage_path('app/public/uploads/lampirans/');

            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            if (in_array($file->extension(), ['jpg', 'jpeg', 'png'])) {
                \Image::make($file->getRealPath())
                    ->resize(500, 500, function ($constraint) {
                        $constraint->upsize();
                        $constraint->aspectRatio();
                    })
                    ->save($path . $filename);
            } else {
                $file->move($path, $filename);
            }

            // Simpan hanya nama file
            $data['lampiran'] = $filename;
        }

        DB::table('tiket_aduans')->where('id', $id)->update($data);

        return apiResponse(true, 'Tiket berhasil diperbarui');
    }


    public function delete(Request $request, $id)
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $tiket = DB::table('tiket_aduans')->where('id', $id)->first();
        if (!$tiket) {
            return apiResponse(false, 'Tiket tidak ditemukan', [], 404);
        }

        // Hapus file jika ada
        if ($tiket->lampiran) {
            $filePath = storage_path('app/public/uploads/lampirans/' . $tiket->lampiran);
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        DB::table('tiket_aduans')->where('id', $id)->delete();

        return apiResponse(true, 'Tiket berhasil dihapus');
    }


    public function getById(Request $request, $id)
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $tiket = DB::table('tiket_aduans')->where('id', $id)->first();
        if (!$tiket) return apiResponse(false, 'Tiket tidak ditemukan', [], 404);

        // Konversi ke array agar bisa dimodifikasi
        $tiket = (array) $tiket;

        // Update URL lampiran jika ada
        if (!empty($tiket['lampiran'])) {
            $tiket['lampiran_url'] = asset('storage/uploads/lampirans/' . $tiket['lampiran']);
        } else {
            $tiket['lampiran_url'] = null;
        }

        return apiResponse(true, 'Data tiket ditemukan', ['tiket' => $tiket]);
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
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $status = $request->input('status');

        // Query builder
        $query = DB::table('tiket_aduans')
            ->where('pelanggan_id', $pelangganId);

        // Apply filters
        if ($startDate) {
            $query->whereDate('tanggal_aduan', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('tanggal_aduan', '<=', $endDate);
        }
        if ($status && $status !== 'Semua') {
            $query->where('status', $status);
        }

        // Ambil total data
        $total = $query->count();

        // Ambil data tiket
        $tiket = $query
            ->orderByDesc('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return apiResponse(true, 'Data tiket berhasil diambil', [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'data' => $tiket,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $tiket = DB::table('tiket_aduans')->where('id', $id)->first();
        if (!$tiket) return apiResponse(false, 'Tiket tidak ditemukan', [], 404);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Menunggu,Diproses,Selesai,Dibatalkan',
        ]);

        if ($validator->fails()) {
            return apiResponse(false, 'Validasi gagal', ['errors' => $validator->errors()], 422);
        }

        DB::table('tiket_aduans')->where('id', $id)->update([
            'status' => $request->status,
            'updated_at' => now()
        ]);

        return apiResponse(true, 'Status tiket berhasil diperbarui');
    }
}
