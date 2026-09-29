<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CasaIglesiaController extends Controller
{
    public function index(): JsonResponse
    {
        $casas = DB::table('casa_iglesia')
            ->select('id_casa', 'nombre_casa', 'direccion', 'estado')
            ->orderBy('nombre_casa')
            ->get();

        return response()->json($casas);
    }

    public function create()
    {
        return view('casas.crear');
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre_casa' => ['required', 'string', 'max:100'],
            'direccion' => ['required', 'string', 'max:200'],
        ]);

        DB::table('casa_iglesia')->insert([
            'nombre_casa' => $datos['nombre_casa'],
            'direccion' => $datos['direccion'],
            'estado' => true,
            'id_lider_responsable' => null,
        ]);

        return redirect('/panel/casas');
    }

    public function edit(int $id)
    {
        $casa = DB::table('casa_iglesia')
            ->where('id_casa', $id)
            ->first();

        abort_if($casa === null, 404);

        $lideres = DB::table('usuario as u')
            ->join('rol as r', 'r.id_rol', '=', 'u.id_rol')
            ->where('u.estado', true)
            ->where('r.nombre_rol', 'Líder')
            ->select('u.id_usuario', 'u.nombre')
            ->orderBy('u.nombre')
            ->get();

        return view('casas.editar', compact('casa', 'lideres'));
    }

    public function update(Request $request, int $id)
    {
        $existe = DB::table('casa_iglesia')
            ->where('id_casa', $id)
            ->exists();

        abort_unless($existe, 404);

        $datos = $request->validate([
            'nombre_casa' => ['required', 'string', 'max:100'],
            'direccion' => ['required', 'string', 'max:200'],
            'id_lider_responsable' => [
                'nullable',
                'integer',
                'exists:usuario,id_usuario',
            ],
        ]);

        $idLider = $datos['id_lider_responsable'] ?? null;

        if ($idLider !== null) {
            $liderValido = DB::table('usuario as u')
                ->join('rol as r', 'r.id_rol', '=', 'u.id_rol')
                ->where('u.id_usuario', $idLider)
                ->where('u.estado', true)
                ->where('r.nombre_rol', 'Líder')
                ->exists();

            if (! $liderValido) {
                throw ValidationException::withMessages([
                    'id_lider_responsable' => 'Selecciona un líder activo.',
                ]);
            }
        }

        DB::table('casa_iglesia')
            ->where('id_casa', $id)
            ->update([
                'nombre_casa' => $datos['nombre_casa'],
                'direccion' => $datos['direccion'],
                'id_lider_responsable' => $idLider,
            ]);

        return redirect('/casas');
    }

    public function deactivate(int $id)
    {
        $actualizadas = DB::table('casa_iglesia')
            ->where('id_casa', $id)
            ->where('estado', true)
            ->update(['estado' => false]);

        abort_if($actualizadas === 0, 404);

        return redirect('/casas');
    }
}