<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Exception $e, $request) {
            if ($request->is('api/*')) {
                // Get status code from exception, default to 500 if not valid HTTP code
                $statusCode = $e->getCode();
                $statusCode = is_int($statusCode) && $statusCode >= 100 && $statusCode < 600 ? $statusCode : 500;

                return response()->json([
                    'message' => $e->getMessage()
                ], $statusCode);
            }

            return false;
        });
    })->create();
