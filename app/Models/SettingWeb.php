<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingWeb extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'setting_web';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['nama_perusahaan', 'telepon_perusahaan', 'email', 'no_wa', 'alamat', 'deskripsi_perusahaan', 'logo', 'url_tripay', 'api_key_tripay', 'kode_merchant', 'private_key', 'nominal_referal', 'is_wa_broadcast_active', 'is_wa_billing_active', 'is_wa_payment_active', 'is_wa_welcome_active', 'video_url_1', 'video_url_2'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */

    /**
     * The attributes that should be cast.
     *
     * @var string[]
     */
    protected $casts = ['nama_perusahaan' => 'string', 'telepon_perusahaan' => 'string', 'email' => 'string', 'no_wa' => 'string', 'alamat' => 'string', 'deskripsi_perusahaan' => 'string', 'logo' => 'string', 'url_tripay' => 'string', 'api_key_tripay' => 'string', 'kode_merchant' => 'string', 'private_key' => 'string', 'is_wa_broadcast_active' => 'string', 'is_wa_billing_active' => 'string', 'is_wa_payment_active' => 'string', 'is_wa_welcome_active' => 'string', 'video_url_1' => 'string', 'video_url_2' => 'string', 'created_at' => 'datetime:d/m/Y H:i', 'updated_at' => 'datetime:d/m/Y H:i'];
}
