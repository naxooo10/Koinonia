<?php

namespace App\Http\Controllers;

use App\Support\ReglasContrasena;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PerfilController extends Controller
{
    public function show(Request $request)
    {
        $usuario = $request->user();

        $rol = DB::connection('mongodb')
            ->table('roles')
            ->where('_id', (int) $usuario->id_rol)
            ->first();

        $idsCasas = array_map(
            'intval',
            (array) ($usuario->casas_ids ?? [])
        );

        $casas = collect();

        if ($idsCasas !== []) {
            $casas = DB::connection('mongodb')
                ->table('casas_iglesia')
                ->whereIn('_id', $idsCasas)
                ->orderBy('nombre_casa')
                ->get()
                ->map(fn ($casa) => (object) [
                    'nombre_casa' => $casa->nombre_casa,
                    'estado' => (bool) $casa->estado,
                ]);
        }

        $perfil = (object) [
            'nombre' => $usuario->nombre,
            'email' => $usuario->email,
            'nombre_rol' => $rol->nombre_rol ?? 'Sin rol',
        ];

        $ayudaContrasena = ReglasContrasena::AYUDA;

        return view(
            'perfil.mostrar',
            compact('perfil', 'casas', 'ayudaContrasena')
        );
    }

    /**
     * Cada persona solo edita su nombre y su correo. El rol, el estado
     * y las Casas siguen siendo decisión del administrador, por eso
     * aquí se ignora cualquier otro campo enviado.
     */
    public function update(Request $request)
    {
        $id = (int) $request->user()->getAuthIdentifier();

        $datos = $request->validate(
            [
                'nombre' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email', 'max:100'],
            ],
            [
                'nombre.required' => 'Ingresa tu nombre.',
                'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
                'email.required' => 'Ingresa tu correo.',
                'email.email' => 'Ingresa un correo válido.',
                'email.max' => 'El correo no puede superar los 100 caracteres.',
            ]
        );

        $correoEnUso = DB::connection('mongodb')
            ->table('usuarios')
            ->where('email', $datos['email'])
            ->where('_id', '!=', $id)
            ->exists();

        if ($correoEnUso) {
            throw ValidationException::withMessages([
                'email' => 'Este correo ya está registrado.',
            ]);
        }

        DB::connection('mongodb')
            ->table('usuarios')
            ->where('_id', $id)
            ->update([
                'nombre' => trim($datos['nombre']),
                'email' => $datos['email'],
            ]);

        return redirect('/perfil')
            ->with('exito', 'Tus datos se actualizaron correctamente.');
    }

    public function updatePassword(Request $request)
    {
        $id = (int) $request->user()->getAuthIdentifier();

        $datos = $request->validate(
            [
                'password_actual' => [
                    'required',
                    'string',
                    'current_password:web',
                ],
                'password' => array_merge(
                    ['required'],
                    ReglasContrasena::reglas(),
                    ['confirmed', 'different:password_actual']
                ),
            ],
            array_merge(
                ReglasContrasena::mensajes(),
                [
                    'password_actual.required' => 'Ingresa tu contraseña actual.',
                    'password_actual.current_password' => 'La contraseña actual no es correcta.',
                ]
            )
        );

        $nuevoHash = Hash::make($datos['password']);

        DB::connection('mongodb')
            ->table('usuarios')
            ->where('_id', $id)
            ->update([
                'password' => $nuevoHash,
            ]);

        // La contraseña se guardó con una consulta directa, así que el
        // usuario en memoria aún tiene el hash anterior. Se sincroniza
        // para que AuthenticateSession registre el hash nuevo en esta
        // sesión: este dispositivo sigue dentro y los demás se cierran
        // en su siguiente petición.
        $usuario = $request->user();
        $usuario->setRawAttributes(array_merge(
            $usuario->getAttributes(),
            ['password' => $nuevoHash]
        ));

        $request->session()->regenerate();

        return redirect('/perfil')
            ->with('exito', 'Tu contraseña se cambió correctamente. Las sesiones abiertas en otros dispositivos se cerraron.');
    }
}
