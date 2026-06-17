<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use App\Services\ErrorLogRecorder;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(HandleCors::class);
        $middleware->appendToGroup('api', \App\Http\Middleware\XRobotsTagMiddleware::class);
        // Centralized admin auth (C1): enforces on /admin/* only, no-op elsewhere.
        $middleware->appendToGroup('api', \App\Http\Middleware\AdminAuthMiddleware::class);
        $middleware->alias([
            'firm.verified' => \App\Http\Middleware\FirmVerifiedMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Record a short, safe summary of each backend exception into the
        // error_logs table for quick admin visibility. This does NOT stop the
        // framework's default reporter, so the COMPLETE exception + stack trace
        // is still written to storage/logs/laravel.log as before.
        $exceptions->report(function (\Throwable $e): void {
            ErrorLogRecorder::record($e);
        });
    })->create();
