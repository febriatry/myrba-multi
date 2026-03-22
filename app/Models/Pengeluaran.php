<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasTenantScope;

class Pengeluaran extends Model
{
    use HasFactory, HasTenantScope;

     /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pengeluarans';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['tenant_id', 'nominal', 'tanggal', 'keterangan','category_pengeluaran_id'];

    /**
     * The attributes that should be cast.
     *
     * @var string[]
     */
    protected $casts = ['tenant_id' => 'integer', 'nominal' => 'integer', 'tanggal' => 'datetime:d/m/Y H:i', 'keterangan' => 'string', 'created_at' => 'datetime:d/m/Y H:i', 'updated_at' => 'datetime:d/m/Y H:i'];


}
