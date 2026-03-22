<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Pelanggan;

class UpdateWithdrawRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Ambil data pelanggan dari ID yang diinput
        $pelanggan = Pelanggan::find($this->input('pelanggan_id'));

        // Tentukan nilai maksimal withdraw
        $maxWd = $pelanggan ? $pelanggan->balance : 0;

        return [
            'pelanggan_id' => 'required|exists:pelanggans,id',
            // Gunakan variabel $maxWd untuk validasi 'max'
            'nominal_wd' => 'required|numeric|min:1|max:' . $maxWd,
            'tanggal_wd' => 'required|date',
        ];
    }

    public function messages()
    {
        return [
            'nominal_wd.max' => 'Nominal withdraw tidak boleh melebihi saldo pelanggan yang tersedia.',
        ];
    }
}
