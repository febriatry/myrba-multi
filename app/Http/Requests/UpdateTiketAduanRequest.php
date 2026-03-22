<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTiketAduanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
			'pelanggan_id' => 'required|exists:App\Models\Pelanggan,id',
			'deskripsi_aduan' => 'required|string',
			'tanggal_aduan' => 'required',
			'status' => 'required|in:Menunggu,Diproses,Selesai,Dibatalkan',
			'prioritas' => 'required|in:Rendah,Sedang,Tinggi',
			'lampiran' => 'nullable|image|max:5000',
        ];
    }
}
