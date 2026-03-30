<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pelanggan extends Model
{
    use HasFactory, LogsActivity;

    protected static function booted()
    {
        static::addGlobalScope('tenant', function ($builder) {
            $user = auth()->user();
            if ($user && isset($user->tenant_id)) {
                $builder->where('tenant_id', (int) $user->tenant_id);
            }
        });
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pelanggans';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['tenant_id', 'coverage_area', 'odc', 'odp', 'no_port_odp', 'no_layanan', 'nama', 'tanggal_daftar', 'email', 'no_wa', 'no_ktp', 'photo_ktp', 'alamat', 'password', 'ppn', 'status_berlangganan', 'material_status', 'material_approved_by', 'material_approved_at', 'paket_layanan', 'pending_paket_layanan', 'pending_paket_effective_periode', 'pending_paket_requested_by', 'pending_paket_requested_at', 'pending_paket_note', 'jatuh_tempo', 'kirim_tagihan_wa', 'latitude', 'longitude', 'auto_isolir', 'tempo_isolir', 'router', 'user_pppoe', 'mode_user', 'user_static', 'balance', 'kode_referal', 'referral_bonus_paid_at', 'genieacs_device_id', 'genieacs_status_json', 'genieacs_last_inform_at', 'genieacs_synced_at'];

    /**
     * The attributes that should be cast.
     *
     * @var string[]
     */
    protected $casts = ['tenant_id' => 'integer', 'no_layanan' => 'integer', 'nama' => 'string', 'tanggal_daftar' => 'date:d/m/Y', 'email' => 'string', 'no_wa' => 'integer', 'no_ktp' => 'string', 'photo_ktp' => 'string', 'alamat' => 'string', 'password' => 'string', 'pending_paket_layanan' => 'integer', 'pending_paket_requested_at' => 'datetime:Y-m-d H:i:s', 'jatuh_tempo' => 'integer', 'latitude' => 'string', 'longitude' => 'string', 'tempo_isolir' => 'integer', 'user_pppoe' => 'string', 'material_approved_at' => 'datetime:Y-m-d H:i:s', 'referral_bonus_paid_at' => 'datetime:Y-m-d H:i:s', 'genieacs_status_json' => 'array', 'genieacs_last_inform_at' => 'datetime:Y-m-d H:i:s', 'genieacs_synced_at' => 'datetime:Y-m-d H:i:s', 'created_at' => 'datetime:d/m/Y H:i', 'updated_at' => 'datetime:d/m/Y H:i'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var string[]
     */
    protected $hidden = ['password'];

    public function area_coverage()
    {
        return $this->belongsTo(\App\Models\AreaCoverage::class);
    }

    public function odc()
    {
        return $this->belongsTo(\App\Models\Odc::class);
    }

    public function odp()
    {
        return $this->belongsTo(\App\Models\Odp::class);
    }

    public function package()
    {
        return $this->belongsTo(\App\Models\Package::class);
    }

    public function settingmikrotik()
    {
        return $this->belongsTo(\App\Models\Settingmikrotik::class);
    }
}
