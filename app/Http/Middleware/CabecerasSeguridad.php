<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CabecerasSeguridad
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        $cabeceras = [
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
            // Las vistas usan scripts y estilos en línea, por eso se
            // permite 'unsafe-inline'; el resto queda limitado al
            // propio sitio.
            'Content-Security-Policy' => implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline'",
                "style-src 'self' 'unsafe-inline'",
                "img-src 'self' data:",
                "font-src 'self' data:",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'",
            ]),
        ];

        if ($request->isSecure()) {
            $cabeceras['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($cabeceras as $nombre => $valor) {
            if (! $respuesta->headers->has($nombre)) {
                $respuesta->headers->set($nombre, $valor);
            }
        }

        return $respuesta;
    }
}
