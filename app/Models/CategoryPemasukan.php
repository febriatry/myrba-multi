<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasTenantScope;

class CategoryPemasukan extends Model
{
    use HasFactory, HasTenantScope;

     /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'category_pemasukans';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['tenant_id', 'nama_kategori_pemasukan'];

    /**
     * The attributes that should be cast.
     *
     * @var string[]
     */
    protected $casts = ['tenant_id' => 'integer', 'nama_kategori_pemasukan' => 'string', 'created_at' => 'datetime:d/m/Y H:i', 'updated_at' => 'datetime:d/m/Y H:i'];
    

}
