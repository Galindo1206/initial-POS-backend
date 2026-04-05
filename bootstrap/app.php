<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php', // ← AGREGA ESTA LÍNEA
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)

->withMiddleware(function (Middleware $middleware): void {
    

    $middleware->use([
        HandleCors::class,   // ✅ AGREGA ESTO
    ]);

    $middleware->alias([
    'role' => \App\Http\Middleware\RoleMiddleware::class,
    'block.waiter.register' => \App\Http\Middleware\BlockWaiterIfRegisterOpen::class,
]);


    $middleware->api();

    $middleware->validateCsrfTokens(except: [
        'api/*',
    ]);
})


    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
