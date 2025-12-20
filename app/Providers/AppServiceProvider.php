<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('InApp', function () {
            return "<?php if (request()->routeIs('login') || request()->routeIs('register')): ?>";
        });

        Blade::directive('endInApp', function () {
            return "<?php endif; ?>";
        });
    }
}
