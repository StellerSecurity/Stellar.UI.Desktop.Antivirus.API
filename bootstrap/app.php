<?php

use Illuminate\Routing\Middleware\ThrottleRequests;
use App\Http\Middleware\TrustProxies;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Application;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // So Laravel knows real client IP behind Azure / proxy
        $middleware->trustProxies(TrustProxies::class);

        // Route middleware aliases
        $middleware->alias([
            'throttle' => ThrottleRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
