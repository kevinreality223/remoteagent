<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageReceipt extends Model
{
    public $timestamps = true;
    protected $primaryKey = 'client_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'client_id',
        'last_acked_message_id',
        'poll_interval_seconds',
        'last_polled_at',
        'next_poll_at',
    ];

    protected $casts = [
        'last_polled_at' => 'datetime',
        'next_poll_at' => 'datetime',
    ];
}
