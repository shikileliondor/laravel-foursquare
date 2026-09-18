<?php

use App\Http\Middleware\EnsureIsAdmin;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetTeamUrlDefaults;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SetTeamUrlDefaults::class,
        ]);

        $middleware->alias(['admin' => EnsureIsAdmin::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Uniform JSON errors for the API. No stack traces in production.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return match (true) {
                $e instanceof ValidationException => ApiResponse::error(
                    'VALIDATION_ERROR', 'Données invalides', 422, ['fields' => $e->errors()],
                ),
                $e instanceof AuthenticationException => ApiResponse::error(
                    'UNAUTHENTICATED', 'Authentification requise', 401,
                ),
                $e instanceof AuthorizationException => ApiResponse::error(
                    'FORBIDDEN', 'Accès refusé', 403,
                ),
                $e instanceof ModelNotFoundException => ApiResponse::error(
                    Str::upper(Str::snake(class_basename($e->getModel()))).'_NOT_FOUND', 'Ressource introuvable', 404,
                ),
                // Laravel wraps ModelNotFoundException before it reaches us.
                $e instanceof NotFoundHttpException && $e->getPrevious() instanceof ModelNotFoundException => ApiResponse::error(
                    Str::upper(Str::snake(class_basename($e->getPrevious()->getModel()))).'_NOT_FOUND', 'Ressource introuvable', 404,
                ),
                $e instanceof NotFoundHttpException => ApiResponse::error(
                    'NOT_FOUND', 'Ressource introuvable', 404,
                ),
                $e instanceof HttpExceptionInterface => ApiResponse::error(
                    'HTTP_ERROR', $e->getMessage() ?: 'Erreur', $e->getStatusCode(),
                ),
                default => config('app.debug')
                    ? null
                    : ApiResponse::error('SERVER_ERROR', 'Une erreur est survenue', 500),
            };
        });
    })->create();
