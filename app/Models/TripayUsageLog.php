<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripayUsageLog extends Model
{
    use HasFactory;

    protected $table = 'tripay_usage_logs';

    protected $fillable = [
        'tenant_id',
        'merchant_ref',
        'tripay_reference',
        'type',
        'method',
        'status',
        'amount',
        'gateway_mode',
        'paid_at',
        'payload',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'amount' => 'integer',
        'payload' => 'array',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
