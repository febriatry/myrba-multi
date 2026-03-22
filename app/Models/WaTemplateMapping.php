<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaTemplateMapping extends Model
{
    use HasFactory;

    protected $table = 'wa_template_mappings';

    protected $fillable = [
        'template_id',
        'message_type',
        'component_type',
        'component_index',
        'component_sub_type',
        'param_index',
        'source_key',
        'parameter_type',
        'default_value',
        'is_required',
        'notes',
    ];

    protected $casts = [
        'component_index' => 'integer',
        'param_index' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
