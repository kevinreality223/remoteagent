<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
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
    }
}
