<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBarangRequest extends FormRequest
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
            'kode_barang' => 'required|string|max:50',
			'nama_barang' => 'required|string|max:255',
			'unit_satuan_id' => 'required|exists:App\Models\UnitSatuan,id',
			'kategori_barang_id' => 'required|exists:App\Models\KategoriBarang,id',
			'deskripsi_barang' => 'nullable|string',
			'photo_barang' => 'nullable|image|max:5000',
			'stock' => 'nullable',
        ];
    }
}
