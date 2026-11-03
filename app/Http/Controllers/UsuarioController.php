<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use MongoDB\Collection;
use MongoDB\Operation\FindOneAndUpdate;

class UsuarioController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = DB::connection('mongodb')
            ->table('roles')
            ->get()
            ->keyBy('id');

        $casas = DB::connection('mongodb')
            ->table('casas_iglesia')
            ->get()
            ->keyBy('id');

        $usuarios = DB::connection('mongodb')
            ->table('usuarios')
            ->orderBy('nombre')
            ->get()
            ->map(function ($usuario) use ($roles, $casas) {
                $ids = array_map(
                    'intval',
                    (array) ($usuario->casas_ids ?? [])
                );

                $casasUsuario = collect($ids)
                    ->map(function ($id) use ($casas) {
                        $casa = $casas->get($id);

                        return $casa ? [
                            'id_casa' => (int) $casa->id,
                            'nombre_casa' => $casa->nombre_casa,
                        ] : null;
                    })
                    ->filter()
                    ->values();

                return [
                    'id_usuario' => (int) $usuario->id,
                    'nombre' => $usuario->nombre,
                    'email' => $usuario->email,
                    'estado' => (bool) $usuario->estado,
                    'id_rol' => (int) $usuario->id_rol,
                    'nombre_rol' => $roles->get($usuario->id_rol)
                        ->nombre_rol ?? 'Sin rol',
                    'casas_ids' => $ids,
                    'casas' => $casasUsuario->all(),
                    'nombre_casa' => $casasUsuario
                        ->pluck('nombre_casa')
                        ->implode(', '),
                ];
            })
            ->values();

        return response()->json($usuarios);
    }

    public function create()
    {
        $roles = $this->obtenerRoles();

        $casas = DB::connection('mongodb')
            ->table('casas_iglesia')
            ->where('estado', true)
            ->orderBy('nombre_casa')
            ->get()
            ->map(fn ($casa) => (object) [
                'id_casa' => (int) $casa->id,
                'nombre_casa' => $casa->nombre_casa,
                'estado' => (bool) $casa->estado,
            ]);

        return view('usuarios.crear', compact('roles', 'casas'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'casas_ids' => $request->input('casas_ids', []),
        ]);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'password' => ['required', 'string', 'min:8'],
            'id_rol' => ['required', 'integer', 'min:1'],
            'casas_ids' => ['present', 'array'],
            'casas_ids.*' => [
                'required',
                'integer',
                'min:1',
                'distinct',
            ],
        ]);

        $idRol = (int) $datos['id_rol'];
        $idsCasas = array_map('intval', $datos['casas_ids']);

        $this->validarCorreo($datos['email']);
        $this->validarRol($idRol);
        $this->validarCasas($idsCasas);

        DB::connection('mongodb')
            ->table('usuarios')
            ->insert([
                '_id' => $this->siguienteIdUsuario(),
                'nombre' => $datos['nombre'],
                'email' => $datos['email'],
                'password' => Hash::make($datos['password']),
                'estado' => true,
                'id_rol' => $idRol,
                'casas_ids' => $idsCasas,
            ]);

        return redirect('/panel/usuarios');
    }

    public function edit(int $id)
    {
        $usuario = $this->obtenerUsuario($id);

        $usuario->id_usuario = (int) $usuario->id;
        $usuario->casas_ids = array_map(
            'intval',
            (array) ($usuario->casas_ids ?? [])
        );

        unset($usuario->password);

        $roles = $this->obtenerRoles();

        $casas = DB::connection('mongodb')
            ->table('casas_iglesia')
            ->where(function ($consulta) use ($usuario) {
                $consulta->where('estado', true)
                    ->orWhereIn('_id', $usuario->casas_ids);
            })
            ->orderBy('nombre_casa')
            ->get()
            ->map(fn ($casa) => (object) [
                'id_casa' => (int) $casa->id,
                'nombre_casa' => $casa->nombre_casa,
                'estado' => (bool) $casa->estado,
            ]);

        return view(
            'usuarios.editar',
            compact('usuario', 'roles', 'casas')
        );
    }

    public function update(Request $request, int $id)
    {
        $usuario = $this->obtenerUsuario($id);

        if ($request->boolean('actualizar_casas')) {
            $request->merge([
                'casas_ids' => $request->input('casas_ids', []),
            ]);
        }

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'id_rol' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
            ],
            'casas_ids' => ['sometimes', 'array'],
            'casas_ids.*' => [
                'required',
                'integer',
                'min:1',
                'distinct',
            ],
        ]);

        $this->validarCorreo($datos['email'], $id);

        $cambios = [
            'nombre' => $datos['nombre'],
            'email' => $datos['email'],
        ];

        if (array_key_exists('id_rol', $datos)) {
            $idRol = (int) $datos['id_rol'];
            $rol = $this->validarRol($idRol);

            if ($idRol !== (int) $usuario->id_rol) {
                $this->protegerCambioDeRol(
                    $request,
                    $usuario,
                    $rol
                );
            }

            $tieneCasasACargo = DB::connection('mongodb')
                ->table('casas_iglesia')
                ->where('id_lider_responsable', $id)
                ->exists();

            if (
                $tieneCasasACargo
                && $rol->nombre_rol !== 'Líder'
            ) {
                throw ValidationException::withMessages([
                    'id_rol' => 'Antes de cambiar el rol, reasigna las Casas que están bajo su responsabilidad.',
                ]);
            }

            $cambios['id_rol'] = $idRol;
        }

        if (array_key_exists('casas_ids', $datos)) {
            $idsCasas = array_map('intval', $datos['casas_ids']);

            $idsAnteriores = array_map(
                'intval',
                (array) ($usuario->casas_ids ?? [])
            );

            $this->validarCasas($idsCasas, $idsAnteriores);

            $cambios['casas_ids'] = $idsCasas;
        }

        DB::connection('mongodb')
            ->table('usuarios')
            ->where('_id', $id)
            ->update($cambios);

        return redirect('/panel/usuarios');
    }

    public function deactivate(Request $request, int $id)
    {
        $usuario = $this->obtenerUsuario($id);

        abort_if(
            $request->user() === null,
            401,
            'Debes iniciar sesión.'
        );

        abort_if(
            (int) $request->user()->getAuthIdentifier() === $id,
            403,
            'No puedes desactivar tu propia cuenta.'
        );

        if ($usuario->estado) {
            $this->protegerUltimoAdministrador($usuario);

            $tieneCasasACargo = DB::connection('mongodb')
                ->table('casas_iglesia')
                ->where('id_lider_responsable', $id)
                ->exists();

            abort_if(
                $tieneCasasACargo,
                403,
                'Antes de desactivar este usuario, reasigna las Casas que están bajo su responsabilidad.'
            );
        }

        DB::connection('mongodb')
            ->table('usuarios')
            ->where('_id', $id)
            ->update(['estado' => false]);

        return redirect('/panel/usuarios');
    }

    public function activate(int $id)
    {
        $this->obtenerUsuario($id);

        DB::connection('mongodb')
            ->table('usuarios')
            ->where('_id', $id)
            ->update(['estado' => true]);

        return redirect('/panel/usuarios');
    }

    private function obtenerUsuario(int $id): object
    {
        $usuario = DB::connection('mongodb')
            ->table('usuarios')
            ->where('_id', $id)
            ->first();

        abort_if($usuario === null, 404, 'El usuario no existe.');

        return $usuario;
    }

    private function protegerCambioDeRol(
        Request $request,
        object $usuario,
        object $nuevoRol
    ): void {
        if ($nuevoRol->nombre_rol === 'Administrador') {
            return;
        }

        $rolActual = $this->validarRol((int) $usuario->id_rol);

        if ($rolActual->nombre_rol !== 'Administrador') {
            return;
        }

        abort_if(
            $request->user() === null,
            401,
            'Debes iniciar sesión.'
        );

        if (
            (int) $request->user()->getAuthIdentifier()
            === (int) $usuario->id
        ) {
            throw ValidationException::withMessages([
                'id_rol' => 'No puedes quitarte el rol de Administrador desde tu propia sesión.',
            ]);
        }

        if ($usuario->estado) {
            $this->protegerUltimoAdministrador(
                $usuario,
                'id_rol'
            );
        }
    }

    private function protegerUltimoAdministrador(
        object $usuario,
        ?string $campo = null
    ): void {
        $rol = $this->validarRol((int) $usuario->id_rol);

        if (
            $rol->nombre_rol !== 'Administrador'
            || ! $usuario->estado
        ) {
            return;
        }

        $existeOtroAdministrador = DB::connection('mongodb')
            ->table('usuarios')
            ->where('id_rol', (int) $rol->id)
            ->where('estado', true)
            ->where('_id', '!=', (int) $usuario->id)
            ->exists();

        if ($existeOtroAdministrador) {
            return;
        }

        $mensaje = 'Debe permanecer al menos un administrador activo en el sistema.';

        if ($campo !== null) {
            throw ValidationException::withMessages([
                $campo => $mensaje,
            ]);
        }

        abort(403, $mensaje);
    }

    private function obtenerRoles()
    {
        return DB::connection('mongodb')
            ->table('roles')
            ->orderBy('_id')
            ->get()
            ->map(fn ($rol) => (object) [
                'id_rol' => (int) $rol->id,
                'nombre_rol' => $rol->nombre_rol,
            ]);
    }

    private function validarCorreo(
        string $email,
        ?int $idIgnorado = null
    ): void {
        $consulta = DB::connection('mongodb')
            ->table('usuarios')
            ->where('email', $email);

        if ($idIgnorado !== null) {
            $consulta->where('_id', '!=', $idIgnorado);
        }

        if ($consulta->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Este correo ya está registrado.',
            ]);
        }
    }

    private function validarRol(int $idRol): object
    {
        $rol = DB::connection('mongodb')
            ->table('roles')
            ->where('_id', $idRol)
            ->first();

        if ($rol === null) {
            throw ValidationException::withMessages([
                'id_rol' => 'Selecciona un rol existente.',
            ]);
        }

        return $rol;
    }

    private function validarCasas(
        array $idsCasas,
        array $idsAnteriores = []
    ): void {
        if ($idsCasas === []) {
            return;
        }

        $casas = DB::connection('mongodb')
            ->table('casas_iglesia')
            ->whereIn('_id', $idsCasas)
            ->get()
            ->keyBy('id');

        foreach ($idsCasas as $idCasa) {
            $casa = $casas->get($idCasa);

            if ($casa === null) {
                throw ValidationException::withMessages([
                    'casas_ids' => 'Una de las Casas seleccionadas no existe.',
                ]);
            }

            if (
                ! $casa->estado
                && ! in_array($idCasa, $idsAnteriores, true)
            ) {
                throw ValidationException::withMessages([
                    'casas_ids' => 'Solo puedes agregar pertenencias a Casas activas.',
                ]);
            }
        }
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