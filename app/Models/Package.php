<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasTenantScope;

class Package extends Model
{
    use HasFactory, HasTenantScope;

     /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'packages';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['tenant_id', 'nama_layanan','harga', 'referral_bonus', 'kategori_paket_id', 'keterangan', 'is_active','profile'];

    /**
     * The attributes that should be cast.
     *
     * @var string[]
     */
    protected $casts = ['tenant_id' => 'integer', 'nama_layanan' => 'string', 'harga' => 'integer', 'referral_bonus' => 'integer', 'keterangan' => 'string', 'created_at' => 'datetime:d/m/Y H:i', 'updated_at' => 'datetime:d/m/Y H:i'];


	public function package_category()
	{
		return $this->belongsTo(\App\Models\PackageCategory::class);}
}
