<?php

namespace App\Http\Controllers;

use App\Support\ReglasContrasena;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use MongoDB\Collection;
use MongoDB\Operation\FindOneAndUpdate;

class RegistroController extends Controller
{
    private const ROL_INICIAL = 'Participante';

    public function show()
    {
        $ayudaContrasena = ReglasContrasena::AYUDA;

        return view('auth.registro', compact('ayudaContrasena'));
    }

    /**
     * Registro público. La cuenta siempre nace como Participante, sin
     * Casas e inactiva, marcada como pendiente de aprobación: no puede
     * iniciar sesión hasta que un administrador la active. El rol, el
     * estado y las Casas no se leen de la solicitud, de modo que nadie
     * puede registrarse como Líder o Administrador ni aprobarse solo.
     */
    public function store(Request $request)
    {
        $datos = $request->validate(
            [
                'nombre' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email', 'max:100'],
                'password' => array_merge(
                    ['required'],
                    ReglasContrasena::reglas(),
                    ['confirmed']
                ),
            ],
            array_merge(
                ReglasContrasena::mensajes(),
                [
                    'nombre.required' => 'Ingresa tu nombre.',
                    'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
                    'email.required' => 'Ingresa tu correo.',
                    'email.email' => 'Ingresa un correo válido.',
                    'email.max' => 'El correo no puede superar los 100 caracteres.',
                ]
            )
        );

        $correoEnUso = DB::connection('mongodb')
            ->table('usuarios')
            ->where('email', $datos['email'])
            ->exists();

        if ($correoEnUso) {
            throw ValidationException::withMessages([
                'email' => 'Este correo ya está registrado. Inicia sesión con tu cuenta.',
            ]);
        }

        $rol = DB::connection('mongodb')
            ->table('roles')
            ->where('nombre_rol', self::ROL_INICIAL)
            ->first();

        abort_if(
            $rol === null,
            503,
            'El registro no está disponible por ahora. Inténtalo más tarde.'
        );

        DB::connection('mongodb')
            ->table('usuarios')
            ->insert([
                '_id' => $this->siguienteIdUsuario(),
                'nombre' => trim($datos['nombre']),
                'email' => $datos['email'],
                'password' => Hash::make($datos['password']),
                'estado' => false,
                'pendiente_aprobacion' => true,
                'id_rol' => (int) $rol->id,
                'casas_ids' => [],
            ]);

        return redirect('/login')
            ->with('exito', 'Tu cuenta se creó correctamente. Podrás iniciar sesión cuando un administrador la apruebe.');
    }

    private function siguienteIdUsuario(): int
    {
        $maximoActual = (int) (
            DB::connection('mongodb')
                ->table('usuarios')
                ->max('_id') ?? 0
        );

        return DB::connection('mongodb')
            ->table('contadores')
            ->raw(function (Collection $coleccion) use ($maximoActual) {
                $coleccion->updateOne(
                    ['_id' => 'usuarios'],
                    ['$max' => ['secuencia' => $maximoActual]],
                    ['upsert' => true]
                );

                $contador = $coleccion->findOneAndUpdate(
                    ['_id' => 'usuarios'],
                    ['$inc' => ['secuencia' => 1]],
                    [
                        'returnDocument' =>
                            FindOneAndUpdate::RETURN_DOCUMENT_AFTER,
                    ]
                );

                return (int) $contador['secuencia'];
            });
    }
}
