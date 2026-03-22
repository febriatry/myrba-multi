<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'barang';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['kode_barang', 'nama_barang', 'unit_satuan_id', 'kategori_barang_id', 'deskripsi_barang', 'photo_barang', 'stock'];

    /**
     * The attributes that should be cast.
     *
     * @var string[]
     */
    protected $casts = ['kode_barang' => 'string', 'nama_barang' => 'string', 'deskripsi_barang' => 'string', 'photo_barang' => 'string', 'stock' => 'integer', 'created_at' => 'datetime:d/m/Y H:i', 'updated_at' => 'datetime:d/m/Y H:i'];


    public function unit_satuan()
    {
        return $this->belongsTo(\App\Models\UnitSatuan::class);
    }

    public function kategori_barang()
    {
        return $this->belongsTo(\App\Models\KategoriBarang::class);
    }
}
