<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credenciales = [
            'email' => $datos['email'],
            'password' => $datos['password'],
            'estado' => true,
        ];

        if (! Auth::guard('web')->attempt($credenciales)) {
            throw ValidationException::withMessages([
                'email' => $this->esCuentaPendiente($datos)
                    ? 'Tu cuenta está pendiente de aprobación. Podrás iniciar sesión cuando un administrador la active.'
                    : 'Las credenciales no son válidas o la cuenta está inactiva.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended('/panel');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    /**
     * El aviso de cuenta pendiente solo se muestra si la contraseña es
     * correcta, para no revelar a terceros qué correos están registrados.
     */
    private function esCuentaPendiente(array $datos): bool
    {
        $usuario = DB::connection('mongodb')
            ->table('usuarios')
            ->where('email', $datos['email'])
            ->first();

        return $usuario !== null
            && ! $usuario->estado
            && ! empty($usuario->pendiente_aprobacion)
            && is_string($usuario->password ?? null)
            && Hash::check($datos['password'], $usuario->password);
    }
}