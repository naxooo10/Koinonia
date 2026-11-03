<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class VerificarRol
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$rolesPermitidos
    ): Response {
        $usuario = $request->user();

        abort_if($usuario === null, 401, 'Debes iniciar sesión.');

        if (! $usuario->estado) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Tu cuenta está inactiva.',
                ], 401);
            }

            return redirect('/login')->withErrors([
                'email' => 'Tu cuenta está inactiva.',
            ]);
        }

        $rol = DB::connection('mongodb')
            ->table('roles')
            ->where('_id', (int) $usuario->id_rol)
            ->first();

        abort_if(
            $rol === null
            || ! in_array($rol->nombre_rol, $rolesPermitidos, true),
            403,
            'No tienes permiso para acceder a esta función.'
        );

        return $next($request);
    }
}