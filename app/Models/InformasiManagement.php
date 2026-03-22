<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InformasiManagement extends Model
{
    use HasFactory;

     /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'informasi_management';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['judul', 'deskripsi', 'thumbnail', 'is_aktif'];

    /**
     * The attributes that should be cast.
     *
     * @var string[]
     */
    protected $casts = ['judul' => 'string', 'deskripsi' => 'string', 'thumbnail' => 'string', 'created_at' => 'datetime:d/m/Y H:i', 'updated_at' => 'datetime:d/m/Y H:i'];


}
