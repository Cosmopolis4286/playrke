<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => App\Http\Middleware\Admin::class,
            'commerce' => App\Http\Middleware\Commerce::class,
            'providerRKE' => App\Http\Middleware\Provider::class,
            'provider-commerce' => App\Http\Middleware\ProviderCommerce::class,
            'seller' => App\Http\Middleware\Seller::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
