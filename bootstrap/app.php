<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'check.role' => \App\Http\Middleware\CheckRole::class,
        ]);

        // Runs after the session is started, so it can end the session of an account
        // that was suspended or deactivated while it was signed in.
        // (appendToGroup() silently failed to apply here; web(append:) is the reliable form.)
        $middleware->web(append: [
            \App\Http\Middleware\EnsureAccountIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Report unhandled exceptions to Sentry (pos.rashnaillounge.com project
        // "Rash Nail Lounge CRM"). DSN lives in .env as SENTRY_LARAVEL_DSN.
        Integration::handles($exceptions);
    })->create();
