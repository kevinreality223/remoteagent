<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $cachePath = storage_path('framework/cache/data');
        $viewsPath = storage_path('framework/views');

        foreach ([$cachePath, $viewsPath] as $path) {
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
        }
    }
}
