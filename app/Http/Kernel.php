<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middleware = [
        // global middleware minimal
    ];

    protected $middlewareAliases = [
        'auth.client' => \App\Http\Middleware\AuthenticateClient::class,
    ];
}
