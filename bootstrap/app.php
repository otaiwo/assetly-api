<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))

    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware): void {
        //
    })

    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (ModelNotFoundException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found',
            ], 404);
        });

        $exceptions->render(function (AuthorizationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        });

        $exceptions->render(function (ValidationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        });

    })

    ->create();


// Register rate limiters AFTER app is created
$app->booted(function () {

    RateLimiter::for('login', function (Request $request) {
        return Limit::perMinute(5)
            ->by($request->ip())
            ->response(function (Request $request, array $headers) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many login attempts. Please try again in 1 minute.',
                    'retry_after' => $headers['Retry-After'] ?? 60,
                ], 429, $headers);
            });
    });

    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip());
    });

});

return $app;
