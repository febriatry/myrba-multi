<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function dashboard(Request $request)
    {
        try {
            $apiKeyError = validateApiKey($request);
            if ($apiKeyError) return $apiKeyError;
            $no = $request->query('no_layanan');
            if (!$no) return apiResponse(false, 'no_layanan wajib', [], 422);
            $pelanggan = DB::table('pelanggans')->where('no_layanan', $no)->first();
            if (!$pelanggan) return apiResponse(false, 'Pelanggan tidak ditemukan', [], 404);
            $latestTagihan = DB::table('tagihans')
                ->where('pelanggan_id', $pelanggan->id)
                ->orderBy('id', 'desc')->first();
            $saldoValue = $pelanggan->balance ?? ($pelanggan->saldo ?? 0);
            $saldo = is_numeric($saldoValue) ? (float) $saldoValue : 0;
            $banners = DB::table('banner_managements')
                ->select('id', 'file_banner', 'urutan', 'is_aktif')
                ->where('is_aktif', 'Yes')
                ->orderBy('urutan', 'asc')->get();
            $informasi = DB::table('informasi_management')
                ->select('id', 'judul', DB::raw('deskripsi as isi_informasi'), 'thumbnail', 'is_aktif')
                ->where('is_aktif', 'Yes')
                ->orderBy('id', 'desc')->limit(10)->get()
                ->map(function ($row) {
                    if (!empty($row->thumbnail)) {
                        $row->thumbnail = asset('storage/uploads/thumbnails/' . $row->thumbnail);
                    } else {
                        $row->thumbnail = null;
                    }
                    return $row;
                });
            return apiResponse(true, 'OK', [
                'pelanggan' => [
                    'id' => $pelanggan->id,
                    'nama' => $pelanggan->nama,
                    'no_layanan' => $pelanggan->no_layanan,
                    'status' => $pelanggan->status_berlangganan,
                    'saldo' => $saldo,
                    'balance' => $saldo,
                ],
                'referral' => [
                    'code' => (string) $pelanggan->no_layanan,
                    'link' => url('/r/' . (string) $pelanggan->no_layanan),
                ],
                'tagihan' => $latestTagihan,
                'banners' => $banners,
                'informasi' => $informasi,
            ]);
        } catch (\Throwable $e) {
            return apiResponse(false, 'Terjadi kesalahan pada server', ['error' => $e->getMessage()], 500);
        }
    }

    public function history(Request $request)
    {
        try {
            $apiKeyError = validateApiKey($request);
            if ($apiKeyError) return $apiKeyError;
            $no = $request->query('no_layanan');
            if (!$no) return apiResponse(false, 'no_layanan wajib', [], 422);
            $pelanggan = DB::table('pelanggans')->where('no_layanan', $no)->first();
            if (!$pelanggan) return apiResponse(false, 'Pelanggan tidak ditemukan', [], 404);
            $riwayatTagihan = DB::table('tagihans')
                ->where('pelanggan_id', $pelanggan->id)
                ->orderBy('id', 'desc')->limit(20)->get();
            $riwayatSaldo = [];
            return apiResponse(true, 'OK', [
                'riwayat_tagihan' => $riwayatTagihan,
                'riwayat_saldo' => $riwayatSaldo,
            ]);
        } catch (\Throwable $e) {
            return apiResponse(false, 'Terjadi kesalahan pada server', ['error' => $e->getMessage()], 500);
        }
    }

    public function account(Request $request)
    {
        try {
            $apiKeyError = validateApiKey($request);
            if ($apiKeyError) return $apiKeyError;
            $no = $request->query('no_layanan');
            if (!$no) return apiResponse(false, 'no_layanan wajib', [], 422);
            $pelanggan = DB::table('pelanggans')->where('no_layanan', $no)->first();
            if (!$pelanggan) return apiResponse(false, 'Pelanggan tidak ditemukan', [], 404);
            $pelangganArr = (array) $pelanggan;
            $saldoValue = $pelangganArr['balance'] ?? ($pelangganArr['saldo'] ?? 0);
            $saldo = is_numeric($saldoValue) ? (float) $saldoValue : 0;
            $pelangganArr['saldo'] = $saldo;
            $pelangganArr['balance'] = $saldo;
            return apiResponse(true, 'OK', ['pelanggan' => $pelangganArr]);
        } catch (\Throwable $e) {
            return apiResponse(false, 'Terjadi kesalahan pada server', ['error' => $e->getMessage()], 500);
        }
    }

    public function banners(Request $request)
    {
        try {
            $apiKeyError = validateApiKey($request);
            if ($apiKeyError) return $apiKeyError;
            $banners = DB::table('banner_managements')
                ->select('id', 'file_banner', 'urutan', 'is_aktif')
                ->where('is_aktif', 'Yes')
                ->orderBy('urutan', 'asc')->get();
            return apiResponse(true, 'OK', ['banners' => $banners]);
        } catch (\Throwable $e) {
            return apiResponse(false, 'Terjadi kesalahan pada server', ['error' => $e->getMessage()], 500);
        }
    }

    public function infos(Request $request)
    {
        try {
            $apiKeyError = validateApiKey($request);
            if ($apiKeyError) return $apiKeyError;
            $informasi = DB::table('informasi_management')
                ->select('id', 'judul', 'deskripsi', DB::raw('deskripsi as isi_informasi'), 'thumbnail', 'is_aktif')
                ->where('is_aktif', 'Yes')
                ->orderBy('id', 'desc')->limit(10)->get();
            return apiResponse(true, 'OK', ['informasi' => $informasi]);
        } catch (\Throwable $e) {
            return apiResponse(false, 'Terjadi kesalahan pada server', ['error' => $e->getMessage()], 500);
        }
    }
}
