<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'from_client_id',
        'to_client_id',
        'type',
        'ciphertext',
        'nonce',
        'tag',
    ];

    public $timestamps = true;
}
