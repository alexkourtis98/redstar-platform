<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'brand' => \App\Http\Middleware\DetectBrand::class,
            'entity.selected' => \App\Http\Middleware\EnsureEntitySelected::class,
            'entity.permission' => \App\Http\Middleware\CheckEntityPermission::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\DetectBrand::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
