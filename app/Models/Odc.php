<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasTenantScope;

class Odc extends Model
{
    use HasFactory, HasTenantScope;

     /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'odcs';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['tenant_id', 'kode_odc', 'wilayah_odc', 'nomor_port_olt', 'warna_tube_fo', 'nomor_tiang', 'document', 'description', 'latitude', 'longitude'];

    /**
     * The attributes that should be cast.
     *
     * @var string[]
     */
    protected $casts = ['tenant_id' => 'integer', 'kode_odc' => 'string', 'nomor_port_olt' => 'integer', 'warna_tube_fo' => 'string', 'nomor_tiang' => 'integer', 'document' => 'string', 'description' => 'string', 'latitude' => 'string', 'longitude' => 'string', 'created_at' => 'datetime:d/m/Y H:i', 'updated_at' => 'datetime:d/m/Y H:i'];


	public function area_coverage()
	{
		return $this->belongsTo(\App\Models\AreaCoverage::class);}
}
