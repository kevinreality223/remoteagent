<?php

return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,127.0.0.1,localhost:3000')),
    'expiration' => null,
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),
    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    ],
];
