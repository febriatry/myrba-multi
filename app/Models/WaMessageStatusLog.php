<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaMessageStatusLog extends Model
{
    use HasFactory;

    protected $table = 'wa_message_status_logs';

    protected $fillable = [
        'message_id',
        'recipient_id',
        'status',
        'type',
        'status_at',
        'errors',
        'payload',
    ];

    protected $casts = [
        'errors' => 'array',
        'payload' => 'array',
        'status_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
