<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'code',
        'status',
        'plan_id',
        'features_json',
        'quota_json',
        'wa_provider_mode',
        'wa_ivosight_base_url',
        'wa_ivosight_api_key',
        'wa_ivosight_sender_id',
        'tripay_base_url',
        'tripay_api_key',
        'tripay_merchant_code',
        'tripay_private_key',
        'tripay_provider_mode',
    ];

    protected $casts = [
        'features_json' => 'array',
        'quota_json' => 'array',
    ];

    public function plan()
    {
        return $this->belongsTo(TenantPlan::class, 'plan_id');
    }
}
