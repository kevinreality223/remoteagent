<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'from_client_id', 'to_client_id', 'type', 'ciphertext', 'nonce', 'tag', 'created_at',
    ];
}
