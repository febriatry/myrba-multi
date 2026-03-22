<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigPesanNotif extends Model
{
    use HasFactory;

     /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'config_pesan_notif';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['pesan_notif_pendaftaran', 'pesan_notif_tagihan', 'pesan_notif_pembayaran', 'pesan_notif_kirim_invoice'];

    /**
     * The attributes that should be cast.
     *
     * @var string[]
     */
    protected $casts = ['pesan_notif_pendaftaran' => 'string', 'pesan_notif_tagihan' => 'string', 'pesan_notif_pembayaran' => 'string', 'pesan_notif_kirim_invoice' => 'string', 'created_at' => 'datetime:d/m/Y H:i', 'updated_at' => 'datetime:d/m/Y H:i'];


}
