<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageReceipt extends Model
{
    public $timestamps = true;
    protected $primaryKey = 'client_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['client_id', 'last_acked_message_id'];
}
