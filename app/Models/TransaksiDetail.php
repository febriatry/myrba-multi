<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class TransaksiDetail extends Model
{
    use HasFactory;

    protected $table = 'transaksi_details';
    protected $fillable = [
        'transaksi_id',
        'barang_id',
        'owner_type',
        'owner_user_id',
        'source_type',
        'source_id',
        'jumlah',
        'hpp_unit',
        'harga_jual_unit',
        'purpose',
        'purpose_scope',
        'target_pelanggan_id',
    ];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function ownerUser()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
