<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantPlan extends Model
{
    protected $fillable = [
        'name',
        'code',
        'status',
        'features_json',
        'quota_json',
    ];

    protected $casts = [
        'features_json' => 'array',
        'quota_json' => 'array',
    ];
}

