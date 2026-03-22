<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingWebRequest extends FormRequest
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
            'nama_perusahaan' => 'required|string|max:244',
            'telepon_perusahaan' => 'required|string|max:15',
            'email' => 'required|email',
            'no_wa' => 'required|max:15',
            'alamat' => 'required|string',
            'deskripsi_perusahaan' => 'required|string',
            'logo' => 'nullable|image|max:5000',
            'url_tripay' => 'required|string|max:255',
            'api_key_tripay' => 'required|string|max:255',
            'kode_merchant' => 'required|string|max:255',
            'private_key' => 'required|string|max:255',
            'nominal_referal' => 'required|numeric|min:0',
            'video_url_1' => 'nullable|url',
            'video_url_2' => 'nullable|url',
        ];
    }
}
