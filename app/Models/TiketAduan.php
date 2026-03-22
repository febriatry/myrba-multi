<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TiketAduan extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tiket_aduans';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['nomor_tiket', 'pelanggan_id', 'deskripsi_aduan', 'tanggal_aduan', 'status', 'prioritas', 'lampiran'];

    /**
     * The attributes that should be cast.
     *
     * @var string[]
     */
    protected $casts = ['nomor_tiket' => 'string', 'deskripsi_aduan' => 'string', 'tanggal_aduan' => 'datetime:d/m/Y H:i', 'lampiran' => 'string', 'created_at' => 'datetime:d/m/Y H:i', 'updated_at' => 'datetime:d/m/Y H:i'];


    public function pelanggan()
    {
        return $this->belongsTo(\App\Models\Pelanggan::class);
    }
}
