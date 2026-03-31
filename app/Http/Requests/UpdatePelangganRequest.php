<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePelangganRequest extends FormRequest
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
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        if ($tenantId < 1) {
            $tenantId = 1;
        }
        $pelangganId = (int) ($this->pelanggan->id ?? 0);

        return [
            'coverage_area' => 'required|exists:App\Models\AreaCoverage,id',
            'odc' => 'nullable|exists:App\Models\Odc,id',
            'odp' => 'nullable|exists:App\Models\Odp,id',
            'no_port_odp' => 'nullable',
            'no_layanan' => [
                'required',
                'string',
                'max:12',
                'regex:/^[0-9]+$/',
                Rule::unique('pelanggans', 'no_layanan')->where('tenant_id', $tenantId)->ignore($pelangganId),
            ],
            'nama' => 'required|string|max:255',
            'tanggal_daftar' => 'required|date',
            'email' => [
                'required',
                'email',
                Rule::unique('pelanggans', 'email')->where('tenant_id', $tenantId)->ignore($pelangganId),
            ],
            'no_wa' => 'required|string|max:15',
            'no_ktp' => 'required|string|max:50',
            'photo_ktp' => 'nullable|image|max:3024',
            'alamat' => 'required|string',
            'password' => 'nullable|confirmed',
            'ppn' => 'required|in:Yes,No',
            'status_berlangganan' => 'required|in:Aktif,Non Aktif,Menunggu,Tunggakan',
            'paket_layanan' => 'required|exists:App\Models\Package,id',
            'jatuh_tempo' => 'required|numeric',
            'kirim_tagihan_wa' => 'required|in:Yes,No',
            'latitude' => 'required|string|max:50',
            'longitude' => 'required|string|max:50',
            'auto_isolir' => 'required|in:Yes,No',
            'router' => 'nullable|exists:App\Models\Settingmikrotik,id',
            'mode_user' => 'nullable|required_with:router|string|max:100',
            'user_pppoe' => 'nullable|required_if:mode_user,PPPOE|string|max:100',
            'user_static' => 'nullable|required_if:mode_user,Static|string|max:100',
        ];
    }

    protected function prepareForValidation(): void
    {
        $noLayanan = $this->input('no_layanan');
        if (is_string($noLayanan)) {
            [$tidGuess, $nl] = parsePrefixedNoLayanan($noLayanan);
            if ($tidGuess > 0 && $nl !== '') {
                $this->merge([
                    'no_layanan' => $nl,
                    '_no_layanan_tid_guess' => $tidGuess,
                ]);
            } else {
                $this->merge(['no_layanan' => trim($noLayanan)]);
            }
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $tidGuess = (int) ($this->input('_no_layanan_tid_guess') ?? 0);
            if ($tidGuess > 0) {
                $tenantId = (int) (auth()->user()->tenant_id ?? 0);
                if ($tenantId < 1) {
                    $tenantId = 1;
                }
                if ($tidGuess !== $tenantId) {
                    $validator->errors()->add('no_layanan', 'Prefix no layanan tidak sesuai tenant.');
                }
            }
        });
    }
}
