<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'transaksi';
    protected $fillable = [
        'user_id',
        'kode_transaksi',
        'tanggal_transaksi',
        'jenis_transaksi',
        'keterangan',
    ];
    protected $casts = [
        'tanggal_transaksi' => 'date:d-m-Y',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(TransaksiDetail::class);
    }
}
