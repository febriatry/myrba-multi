<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankController extends Controller
{
    public function getBankAccounts(Request $request)
    {
        // Validasi API Key
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        try {
            $bankAccounts = DB::table('bank_accounts')
                ->join('banks', 'bank_accounts.bank_id', '=', 'banks.id')
                ->select(
                    'bank_accounts.id',
                    'banks.nama_bank',
                    'banks.logo_bank',
                    'bank_accounts.pemilik_rekening',
                    'bank_accounts.nomor_rekening'
                )
                ->get()
                ->map(function ($row) {
                    // Cek jika logo_bank tidak kosong, baru buat URL lengkap.
                    // Jika kosong, kembalikan null.
                    $row->logo_bank = !empty($row->logo_bank)
                        ? asset('storage/uploads/logo_banks/' . $row->logo_bank)
                        : null;
                    return $row;
                });

            return apiResponse(true, 'Berhasil mengambil data rekening bank', [
                'banks' => $bankAccounts
            ]);
        } catch (\Exception $e) {
            return apiResponse(false, 'Terjadi kesalahan pada server: ' . $e->getMessage(), [], 500);
        }
    }
}
