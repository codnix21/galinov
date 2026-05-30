<?php

/**
 * Создание приложения Laravel: маршруты, псевдонимы middleware, обработка исключений.
 */

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
        $middleware->trustProxies(at: '*');

        $middleware->validateCsrfTokens(except: [
            'payment/result',
            'payment/success',
            'payment/fail',
        ]);

        $middleware->alias([
            // Запрет входа заблокированным пользователям
            'check.blocked' => \App\Http\Middleware\CheckUserBlocked::class,
            // Доступ к модерации объявлений (админ / риэлтор)
            'staff.moderate' => \App\Http\Middleware\CanModerateProperties::class,
            'realtor.training' => \App\Http\Middleware\EnsureRealtor::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
