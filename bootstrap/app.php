<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register role-based middleware
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'client' => \App\Http\Middleware\ClientMiddleware::class,
            'trainer' => \App\Http\Middleware\TrainerMiddleware::class,
        ]);
    })
     ->withExceptions(function (Illuminate\Foundation\Configuration\Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                    'data' => ['error' => 'Authentication token is missing or invalid.']
                ], 401);
            }
        });

        // Handle Validation exceptions (422 errors)
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'data' => $e->errors()
                ], 422);
            }
        });

        // Handle Model Not Found exceptions (404 errors from implicit model binding)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                    'data' => ['error' => 'The requested resource does not exist.']
                ], 404);
            }
        });

        // Handle generic HTTP exceptions (403, 405, etc.)
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $statusCode = $e->getStatusCode();
                $message = match($statusCode) {
                    403 => 'Access Denied',
                    405 => 'Method Not Allowed',
                    default => $e->getMessage() ?? 'An error occurred'
                };
                
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'data' => ['error' => $e->getMessage() ?? 'An error occurred']
                ], $statusCode);
            }
        });
    })->create();
