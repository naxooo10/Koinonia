<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccesoDemo
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local')) {
            return $next($request);
        }

        $usuario = config('demo.usuario');
        $clave = config('demo.clave');

        if (
            ! is_string($usuario) || $usuario === '' ||
            ! is_string($clave) || $clave === ''
        ) {
            abort(503, 'Acceso de demostración sin configurar.');
        }

        if (
            ! hash_equals($usuario, (string) $request->getUser()) ||
            ! hash_equals($clave, (string) $request->getPassword())
        ) {
            return response('Se requiere acceso a la demostración.', 401)
                ->header('WWW-Authenticate', 'Basic realm="Koinonia Etapa 1"');
        }

        return $next($request);
    }
}