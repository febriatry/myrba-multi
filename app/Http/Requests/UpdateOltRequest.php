<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOltRequest extends FormRequest
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
            'name' => 'required|string|max:100',
			'type' => 'required|in:Zte,Huawei',
			'host' => 'required|string|max:100',
			'telnet_port' => 'required|numeric',
			'telnet_username' => 'required|string|max:100',
			'telnet_password' => 'required|string|max:100',
			'snmp_port' => 'required|numeric',
			'ro_community' => 'required|string|max:100',
        ];
    }
}
