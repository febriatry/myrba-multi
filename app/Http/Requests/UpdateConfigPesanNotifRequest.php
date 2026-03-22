<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfigPesanNotifRequest extends FormRequest
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
            'pesan_notif_pendaftaran' => 'required|string',
			'pesan_notif_tagihan' => 'required|string',
			'pesan_notif_pembayaran' => 'required|string',
			'pesan_notif_kirim_invoice' => 'required|string',
        ];
    }
}
