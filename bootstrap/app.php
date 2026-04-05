<?php

use App\Http\Middleware\EnsureJsonResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        // AuthServiceProvider должен быть загружен ДО AppServiceProvider
        App\Providers\AuthServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API middleware group (CORS, JSON response)
        // Note: Sanctum stateful middleware removed - handled per-route
        $middleware->group('api', [
            HandleCors::class,
            EnsureJsonResponse::class,
        ]);

        // Public API middleware group (NO Sanctum - for external clients)
        $middleware->group('public-api', [
            HandleCors::class,
            EnsureJsonResponse::class,
        ]);

        // Alias for public-api routes
        $middleware->alias([
            'public-api' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
