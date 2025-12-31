<?php

use Illuminate\Foundation\Application;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('api', function (Request $request) {
    $clientId = optional($request->attributes->get('client'))->id ?? $request->ip();

    return [
        Limit::perMinute(60)->by($clientId.'-api'),
    ];
});

RateLimiter::for('poll', function (Request $request) {
    $clientId = optional($request->attributes->get('client'))->id ?? $request->ip();

    return [
        Limit::perMinute(120)->by($clientId.'-poll'),
    ];
});

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: ['/api/*']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Customize exception handling here.
    })
    ->create();
