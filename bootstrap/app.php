<?php

use App\Http\Middleware\EnsureFeatureEnabled;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveActiveCompany;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'active-company' => ResolveActiveCompany::class,
            'feature' => EnsureFeatureEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Branded Ventia error pages for the statuses users can actually hit
        // (403/404/419/422/500/503) instead of Laravel's bare default views.
        // Gated on APP_DEBUG (not APP_ENV) so Ignition/Whoops still show the
        // real stack trace whenever debug mode is on, in any environment.
        $exceptions->respond(function (HttpResponse $response, Throwable $exception, Request $request) {
            if (
                ! $request->expectsJson()
                && ! config('app.debug')
                && in_array($response->getStatusCode(), [403, 404, 419, 422, 500, 503], true)
            ) {
                return Inertia::render('Error', ['status' => $response->getStatusCode()])
                    ->toResponse($request)
                    ->setStatusCode($response->getStatusCode());
            }

            return $response;
        });
    })->create();
