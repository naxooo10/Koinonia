<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RolController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = DB::table('rol')
            ->select('id_rol', 'nombre_rol')
            ->orderBy('id_rol')
            ->get();

        return response()->json($roles);
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
}