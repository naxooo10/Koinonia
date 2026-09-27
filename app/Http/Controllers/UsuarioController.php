<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function index(): JsonResponse
    {
        $usuarios = DB::table('usuario as u')
            ->join('rol as r', 'r.id_rol', '=', 'u.id_rol')
            ->leftJoin('casa_iglesia as c', 'c.id_casa', '=', 'u.id_casa_asignada')
            ->select(
                'u.id_usuario',
                'u.nombre',
                'u.email',
                'u.estado',
                'r.nombre_rol',
                'c.nombre_casa'
            )
            ->orderBy('u.nombre')
            ->get();

        return response()->json($usuarios);
    }

    public function create()
    {
        $roles = DB::table('rol')
            ->select('id_rol', 'nombre_rol')
            ->orderBy('id_rol')
            ->get();

        $casas = DB::table('casa_iglesia')
            ->where('estado', true)
            ->select('id_casa', 'nombre_casa')
            ->orderBy('nombre_casa')
            ->get();

        return view('usuarios.crear', [
            'roles' => $roles,
            'casas' => $casas,
        ]);
    }

    public function store(Request $request)
    {
        $rolValidado = $request->validate([
            'id_rol' => ['required', 'integer', Rule::exists('rol', 'id_rol')],
        ]);

        $esParticipante = DB::table('rol')
            ->where('id_rol', $rolValidado['id_rol'])
            ->value('nombre_rol') === 'Participante';

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('usuario', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'id_casa_asignada' => [
                Rule::requiredIf($esParticipante),
                Rule::prohibitedIf(!$esParticipante),
                'nullable',
                'integer',
                Rule::exists('casa_iglesia', 'id_casa')->where('estado', 1),
            ],
        ]);

        DB::table('usuario')->insert([
            'nombre' => $datos['nombre'],
            'email' => $datos['email'],
            'password' => Hash::make($datos['password']),
            'estado' => true,
            'id_rol' => $rolValidado['id_rol'],
            'id_casa_asignada' => $datos['id_casa_asignada'] ?? null,
        ]);

        return redirect('/usuarios');
    }
    public function edit(int $id)
{
    $usuario = DB::table('usuario')
        ->where('id_usuario', $id)
        ->first();

    abort_if($usuario === null, 404);

    $roles = DB::table('rol')
        ->select('id_rol', 'nombre_rol')
        ->orderBy('id_rol')
        ->get();

    $casas = DB::table('casa_iglesia')
        ->where('estado', true)
        ->select('id_casa', 'nombre_casa')
        ->orderBy('nombre_casa')
        ->get();

    return view('usuarios.editar', [
        'usuario' => $usuario,
        'roles' => $roles,
        'casas' => $casas,
    ]);
}
public function update(Request $request, int $id)
{
    $existe = DB::table('usuario')
        ->where('id_usuario', $id)
        ->exists();

    abort_unless($existe, 404);

    $datos = $request->validate([
        'nombre' => ['required', 'string', 'max:100'],
        'email' => [
            'required',
            'email',
            'max:100',
            Rule::unique('usuario', 'email')->ignore($id, 'id_usuario'),
        ],
    ]);

    DB::table('usuario')
        ->where('id_usuario', $id)
        ->update($datos);

    return redirect('/usuarios');
}
public function deactivate(int $id)
{
    $actualizadas = DB::table('usuario')
        ->where('id_usuario', $id)
        ->where('estado', true)
        ->update(['estado' => false]);

    abort_if($actualizadas === 0, 404);

    return redirect('/usuarios');
}
}