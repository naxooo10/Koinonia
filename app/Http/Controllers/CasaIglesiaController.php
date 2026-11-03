<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use MongoDB\Collection;
use MongoDB\Operation\FindOneAndUpdate;

class CasaIglesiaController extends Controller
{
    public function index(): JsonResponse
    {
        $casas = DB::connection('mongodb')
            ->table('casas_iglesia')
            ->orderBy('nombre_casa')
            ->get()
            ->map(fn ($casa) => [
                'id_casa' => $casa->id,
                'nombre_casa' => $casa->nombre_casa,
                'direccion' => $casa->direccion,
                'estado' => (bool) $casa->estado,
                'id_lider_responsable' =>
                    $casa->id_lider_responsable ?? null,
            ]);

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

        DB::connection('mongodb')
            ->table('casas_iglesia')
            ->insert([
                '_id' => $this->siguienteIdCasa(),
                'nombre_casa' => $datos['nombre_casa'],
                'direccion' => $datos['direccion'],
                'estado' => true,
                'id_lider_responsable' => null,
            ]);

        return redirect('/panel/casas');
    }

    public function edit(int $id)
    {
        $casa = DB::connection('mongodb')
            ->table('casas_iglesia')
            ->where('_id', $id)
            ->first();

        abort_if($casa === null, 404);

        // La vista actual utiliza id_casa.
        $casa->id_casa = $casa->id;

        $rolLider = DB::connection('mongodb')
            ->table('roles')
            ->where('nombre_rol', 'Líder')
            ->first();

        $lideres = collect();

        if ($rolLider !== null) {
            $lideres = DB::connection('mongodb')
                ->table('usuarios')
                ->where('estado', true)
                ->where('id_rol', $rolLider->id)
                ->orderBy('nombre')
                ->get()
                ->map(fn ($usuario) => (object) [
                    'id_usuario' => $usuario->id,
                    'nombre' => $usuario->nombre,
                ]);
        }

        return view('casas.editar', compact('casa', 'lideres'));
    }

    public function update(Request $request, int $id)
    {
        $existe = DB::connection('mongodb')
            ->table('casas_iglesia')
            ->where('_id', $id)
            ->exists();

        abort_unless($existe, 404);

        $datos = $request->validate([
            'nombre_casa' => ['required', 'string', 'max:100'],
            'direccion' => ['required', 'string', 'max:200'],
            'id_lider_responsable' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ]);

        $idLider = $datos['id_lider_responsable'] ?? null;

        if ($idLider !== null && $idLider !== '') {
            // Los formularios envían textos vacíos como cadenas, así que los convertimos a enteros
            $idLider = (int) $idLider;

            $rolLider = DB::connection('mongodb')
                ->table('roles')
                ->where('nombre_rol', 'Líder')
                ->first();

            $liderValido = false;

            if ($rolLider !== null) {
                $liderValido = DB::connection('mongodb')
                    ->table('usuarios')
                    ->where('_id', $idLider)
                    ->where('estado', true)
                    ->where('id_rol', $rolLider->id)
                    ->exists();
            }

            if (! $liderValido) {
                throw ValidationException::withMessages([
                    'id_lider_responsable' =>
                        'Selecciona un líder activo.',
                ]);
            }
        } else {
            $idLider = null;
        }

        DB::connection('mongodb')
            ->table('casas_iglesia')
            ->where('_id', $id)
            ->update([
                'nombre_casa' => $datos['nombre_casa'],
                'direccion' => $datos['direccion'],
                'id_lider_responsable' => $idLider,
            ]);

        return redirect('/panel/casas');
    }

    public function deactivate(int $id)
    {
        $existe = DB::connection('mongodb')
            ->table('casas_iglesia')
            ->where('_id', $id)
            ->exists();

        abort_unless($existe, 404);

        DB::connection('mongodb')
            ->table('casas_iglesia')
            ->where('_id', $id)
            ->update(['estado' => false]);

        return redirect('/panel/casas');
    }

    public function activate(int $id)
    {
        $existe = DB::connection('mongodb')
            ->table('casas_iglesia')
            ->where('_id', $id)
            ->exists();

        abort_unless($existe, 404);

        DB::connection('mongodb')
            ->table('casas_iglesia')
            ->where('_id', $id)
            ->update(['estado' => true]);

        return redirect('/panel/casas');
    }

    private function siguienteIdCasa(): int
    {
        $maximoActual = (int) (
            DB::connection('mongodb')
                ->table('casas_iglesia')
                ->max('_id') ?? 0
        );

        return DB::connection('mongodb')
            ->table('contadores')
            ->raw(function (Collection $coleccion) use ($maximoActual) {
                // Inicializa o ajusta el contador según los datos existentes
                $coleccion->updateOne(
                    ['_id' => 'casas_iglesia'],
                    ['$max' => ['secuencia' => $maximoActual]],
                    ['upsert' => true]
                );

                // Reserva el siguiente número de secuencia y lo devuelve
                $contador = $coleccion->findOneAndUpdate(
                    ['_id' => 'casas_iglesia'],
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