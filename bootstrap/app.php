<?php

use App\Exceptions\ProjectAlreadyHasBudgetException;
use App\Http\Middleware\EnsureActiveCompany;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active.company' => EnsureActiveCompany::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (
            ProjectAlreadyHasBudgetException $exception,
            Request $request
        ) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 409);
        });

        $exceptions->render(function (
            AccessDeniedHttpException $exception,
            Request $request
        ) {
            return response()->json([
                'message' => 'No tienes permiso para realizar esta acción.',
            ], 403);
        });

        $exceptions->render(function (
            HttpException $exception,
            Request $request
        ) {
            if (
                $request->is('api/*') &&
                $exception->getStatusCode() === 403 &&
                $exception->getMessage() === 'Your email address is not verified.'
            ) {
                return response()->json([
                    'message' => 'Debés verificar tu correo electrónico para acceder.',
                ], 403);
            }

            return null;
        });

        $exceptions->render(function (
            ThrottleRequestsException $exception,
            Request $request
        ) {
            $retryAfter = $exception->getHeaders()['Retry-After'] ?? null;

            return response()->json([
                'message' => 'Has realizado demasiadas solicitudes en poco tiempo. Espera un momento antes de volver a intentarlo.',
                'retry_after' => $retryAfter !== null
                    ? (int) $retryAfter
                    : null,
            ], 429);
        });
    })->create();
