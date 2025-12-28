<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/';

    public function boot(): void
    {
        // Routes loaded via bootstrap/app.php configuration
    }
}
