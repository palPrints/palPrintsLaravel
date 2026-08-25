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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'auth/apple/callback',
        ]);

        $middleware->alias([
            'active' => \App\Http\Middleware\CheckUserActive::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'profile.complete' => \App\Http\Middleware\EnsureRoleProfileComplete::class,
            'account.approved' => \App\Http\Middleware\EnsureBusinessAccountApproved::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // تستخدم صفحات الويب استجابات Laravel الافتراضية وعمليات إعادة التوجيه.
    })->create();
