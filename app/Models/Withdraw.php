<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasTenantScope;

class Withdraw extends Model
{
    use HasFactory, HasTenantScope;

    protected $table = 'withdraws';

    protected $fillable = [
        'tenant_id',
        'pelanggan_id',
        'nominal_wd',
        'status',
        'tanggal_wd',
        'user_approved',
        'catatan_user_approved',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'tanggal_wd' => 'datetime',
    ];

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'user_approved');
    }
}
