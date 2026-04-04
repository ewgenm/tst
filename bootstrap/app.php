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
        // API middleware group
        $middleware->group('api', [
            HandleCors::class,
            EnsureJsonResponse::class,
            EnsureFrontendRequestsAreStateful::class, // Sanctum SPA authentication
            // 'throttle:api', // Rate limiting (настроим позже)
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
