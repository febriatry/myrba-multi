<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTopupRequest extends FormRequest
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
            'no_topup' => 'required|string|max:100',
			'pelanggan_id' => 'required|exists:App\Models\Pelanggan,id',
			'tanggal_topup' => 'required',
			'nominal' => 'required|numeric',
			'status' => 'required|in:pending,success,failed,canceled,refunded,expired',
			'metode_topup' => 'required|string|max:255',
			'payload_tripay' => 'required|string',
			'tanggal_callback_tripay' => 'required',
        ];
    }
}
