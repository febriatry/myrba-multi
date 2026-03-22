<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;
use App\Models\Concerns\HasTenantScope;

class Tagihan extends Model
{
    use HasFactory, LogsActivity, HasTenantScope;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tagihans';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'tenant_id',
        'no_tagihan',
        'pelanggan_id',
        'periode',
        'metode_bayar',
        'status_bayar',
        'nominal_bayar',
        'potongan_bayar',
        'ppn',
        'nominal_ppn',
        'total_bayar',
        'tanggal_bayar',
        'tanggal_create_tagihan',
        'tanggal_kirim_notif_wa',
        'payload_tripay',
        'tripay_reference',
        'is_send',
        'created_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var string[]
     */
    protected $casts = ['tenant_id' => 'integer', 'no_tagihan' => 'string', 'nominal_bayar' => 'integer', 'potongan_bayar' => 'integer', 'total_bayar' => 'integer', 'tanggal_bayar' => 'datetime:d/m/Y H:i', 'tanggal_create_tagihan' => 'datetime:d/m/Y H:i', 'tanggal_kirim_notif_wa' => 'datetime:d/m/Y H:i', 'created_at' => 'datetime:d/m/Y H:i', 'updated_at' => 'datetime:d/m/Y H:i'];


    public function pelanggan()
    {
        return $this->belongsTo(\App\Models\Pelanggan::class);
    }
}
