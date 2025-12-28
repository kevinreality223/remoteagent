<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Client extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'name', 'api_token_hash', 'encryption_key_encrypted',
    ];

    protected static function booted(): void
    {
        static::creating(function (Client $client) {
            if (empty($client->id)) {
                $client->id = (string) Str::uuid();
            }
        });
    }
}
