<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Validasi API Key
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        try {
            $request->validate([
                'no_layanan' => 'required|string',
                'password' => 'required',
            ]);

            $pelanggan = Pelanggan::where('no_layanan', $request->no_layanan)->first();

            if (!$pelanggan || !Hash::check($request->password, $pelanggan->password)) {
                return apiResponse(false, 'ID Pelanggan atau kata sandi yang Anda masukkan salah.', [], 401);
            }

            // Cek status berlangganan
            if ($pelanggan->status_berlangganan !== 'Aktif') {
                return apiResponse(false, 'Akun Anda belum aktif.', [], 403);
            }

            return apiResponse(true, 'Berhasil masuk', [
                'user' => [
                    'id' => $pelanggan->id,
                    'nama' => $pelanggan->nama,
                    'email' => $pelanggan->email,
                    'no_layanan' => $pelanggan->no_layanan,
                    'status_berlangganan' => $pelanggan->status_berlangganan,
                    'paket_layanan' => $pelanggan->paket_layanan,
                ]
            ]);
        } catch (ValidationException $e) {
            return apiResponse(false, 'Terjadi kesalahan validasi', ['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return apiResponse(false, 'Terjadi kesalahan pada server: ' . $e->getMessage(), [], 500);
        }
    }


    public function logout(Request $request)
    {
        // Validasi API Key
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        // Tidak perlu hapus token karena tidak digunakan
        return apiResponse(true, 'Berhasil keluar');
    }

    public function getDetailPelangganById(Request $request, $id)
    {
        // Validasi API Key
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        try {
            $pelanggan = DB::table('pelanggans')
                ->leftJoin('area_coverages', 'pelanggans.coverage_area', '=', 'area_coverages.id')
                ->leftJoin('odcs', 'pelanggans.odc', '=', 'odcs.id')
                ->leftJoin('odps', 'pelanggans.odp', '=', 'odps.id')
                ->leftJoin('packages', 'pelanggans.paket_layanan', '=', 'packages.id')
                ->leftJoin('settingmikrotiks', 'pelanggans.router', '=', 'settingmikrotiks.id')
                ->select(
                    'pelanggans.*',
                    'area_coverages.kode_area',
                    'area_coverages.nama as nama_area',
                    'odcs.kode_odc',
                    'odps.kode_odp',
                    'packages.nama_layanan',
                    'packages.harga',
                    'settingmikrotiks.identitas_router'
                )
                ->where('pelanggans.id', $id)
                ->first();

            if (!$pelanggan) {
                return apiResponse(false, 'Pelanggan tidak ditemukan.', [], 404);
            }

            return apiResponse(true, 'Detail pelanggan berhasil diambil', [
                'user' => $pelanggan
            ]);
        } catch (\Exception $e) {
            return apiResponse(false, 'Terjadi kesalahan pada server: ' . $e->getMessage(), [], 500);
        }
    }

    public function updatePelanggan(Request $request, $id)
    {
        // Validasi API Key
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        try {
            // Validasi input
            $validated = $request->validate([
                'nama' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:pelanggans,email,' . $id,
                'no_wa' => [
                    'required',
                    'string',
                    'regex:/^62[0-9]{9,15}$/',
                ],
                'alamat' => 'nullable|string|max:500',
                'latitude' => 'nullable|string|max:50',
                'longitude' => 'nullable|string|max:50',
                'password' => 'nullable|string|min:6',
            ], [
                'no_wa.regex' => 'Nomor WhatsApp harus dimulai dengan 62 dan hanya berisi angka.',
            ]);

            $pelanggan = Pelanggan::find($id);
            if (!$pelanggan) {
                return apiResponse(false, 'Pelanggan tidak ditemukan.', [], 404);
            }

            // Update data
            $pelanggan->nama = $validated['nama'];
            $pelanggan->email = $validated['email'];
            $pelanggan->no_wa = $validated['no_wa'];
            $pelanggan->alamat = $validated['alamat'] ?? $pelanggan->alamat;
            if (isset($validated['latitude'])) {
                $pelanggan->latitude = $validated['latitude'];
            }
            if (isset($validated['longitude'])) {
                $pelanggan->longitude = $validated['longitude'];
            }

            if (!empty($validated['password'])) {
                $pelanggan->password = bcrypt($validated['password']);
            }

            $pelanggan->save();

            return apiResponse(true, 'Data pelanggan berhasil diperbarui', [
                'user' => [
                    'id' => $pelanggan->id,
                    'nama' => $pelanggan->nama,
                    'email' => $pelanggan->email,
                    'no_wa' => $pelanggan->no_wa,
                    'alamat' => $pelanggan->alamat,
                    'latitude' => $pelanggan->latitude,
                    'longitude' => $pelanggan->longitude,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return apiResponse(false, 'Validasi gagal', ['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return apiResponse(false, 'Terjadi kesalahan pada server: ' . $e->getMessage(), [], 500);
        }
    }
}
