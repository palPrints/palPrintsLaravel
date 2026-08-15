<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'active' => \App\Http\Middleware\CheckUserActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // كل أخطاء /api ترجع JSON حتى لو لم يرسل العميل Accept: application/json.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, \Throwable $exception): bool =>
                $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => 'يجب تسجيل الدخول للوصول إلى هذا المسار.',
            ], 401);
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => 'البيانات المدخلة غير صحيحة.',
                'errors' => $exception->errors(),
            ], $exception->status);
        });
    })->create();
