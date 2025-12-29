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
        'id',
        'name',
        'fingerprint',
        'api_token_hash',
        'api_token_encrypted',
        'encryption_key_encrypted',
        'last_seen_at',
    ];

    protected $hidden = [
        'api_token_hash',
        'api_token_encrypted',
        'encryption_key_encrypted',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
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
