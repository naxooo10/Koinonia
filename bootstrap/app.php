<?php

use App\Http\Middleware\CabecerasSeguridad;
use App\Http\Middleware\NotificacionesWeb;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'rol' => \App\Http\Middleware\VerificarRol::class,
        ]);

        $middleware->web(append: [
            CabecerasSeguridad::class,
            NotificacionesWeb::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) =>
                $request->is('api/*')
                || $request->is('informes/reuniones')
                || $request->expectsJson()
        );

        $exceptions->respond(function (
            Response $respuesta,
            Throwable $error,
            Request $request
        ): Response {
            return app(NotificacionesWeb::class)->decorar($request, $respuesta, $error);
        });
    })
    ->create();
