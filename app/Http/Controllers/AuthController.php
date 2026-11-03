<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
                'email' =>
                    'Las credenciales no son válidas o la cuenta está inactiva.',
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
}