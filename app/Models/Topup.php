<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasTenantScope;

class Topup extends Model
{
    use HasFactory, HasTenantScope;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'topups';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['tenant_id', 'no_topup', 'pelanggan_id', 'tanggal_topup', 'nominal', 'status', 'metode', 'metode_topup', 'payload_tripay', 'tanggal_callback_tripay', 'reviewed_by', 'bukti_topup', 'bank_account_id', 'tanggal_review'];

    /**
     * The attributes that should be cast.
     *
     * @var string[]
     */
    protected $casts = ['tenant_id' => 'integer', 'no_topup' => 'string', 'tanggal_topup' => 'datetime:d/m/Y H:i', 'nominal' => 'integer', 'status' => 'string', 'metode' => 'string', 'metode_topup' => 'string', 'payload_tripay' => 'string', 'tanggal_callback_tripay' => 'datetime:d/m/Y H:i', 'reviewed_by' => 'integer', 'bukti_topup' => 'string', 'bank_account_id' => 'integer', 'tanggal_review' => 'datetime:d/m/Y H:i', 'created_at' => 'datetime:d/m/Y H:i', 'updated_at' => 'datetime:d/m/Y H:i'];


    public function pelanggan()
    {
        return $this->belongsTo(\App\Models\Pelanggan::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(\App\Models\BankAccount::class);
    }
}
