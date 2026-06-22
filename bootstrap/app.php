<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\PostTooLargeException;
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
        // Read-only guard for admin impersonation: no-op unless auth_token is an
        // active impersonation session (is_impersonation = 1).
        $middleware->appendToGroup('api', \App\Http\Middleware\BlockImpersonationWrites::class);
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

        // Graceful "Post data is too large" — PHP rejects an oversized request
        // body (post_max_size) before the controller runs, and Laravel throws a
        // PostTooLargeException. Return the app's standard JSON error shape for
        // API requests so the frontend shows a clean message instead of a raw
        // 413 HTML page. (This case is still recorded in error_logs by the
        // report() hook above.)
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Upload too large. Please reduce file sizes (max 5MB per image, up to 5 office images) and try again.',
                ], 413);
            }
            return null;
        });
    })->create();
