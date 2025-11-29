<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * If you leave this null, controller routes will not have a namespace prefix.
     */
    public const HOME = '/';

    /**
     * Register any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Login attempts – 5 per minute per IP
        RateLimiter::for('stellar-login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Registration – 3 per minute per IP
        RateLimiter::for('stellar-register', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        // Password flows – 5 per minute per IP
        RateLimiter::for('stellar-password', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
