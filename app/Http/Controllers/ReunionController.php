<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReunionController extends Controller
{
    public function create(Request $request)
    {
        $datos = $request->validate([
            'id_casa' => [
                'nullable',
                'integer',
                'exists:casa_iglesia,id_casa',
            ],
        ]);

        $casas = DB::table('casa_iglesia')
            ->where('estado', true)
            ->orderBy('nombre_casa')
            ->get();

        $casa = null;
        $lider = null;
        $participantes = collect();

        if (! empty($datos['id_casa'])) {
            $casa = $casas->firstWhere('id_casa', $datos['id_casa']);

            abort_if($casa === null, 404);

            $lider = DB::table('usuario as u')
                ->join('rol as r', 'r.id_rol', '=', 'u.id_rol')
                ->where('u.id_usuario', $casa->id_lider_responsable)
                ->where('u.estado', true)
                ->where('r.nombre_rol', 'Líder')
                ->select('u.id_usuario', 'u.nombre')
                ->first();

            $participantes = DB::table('usuario as u')
                ->join('rol as r', 'r.id_rol', '=', 'u.id_rol')
                ->where('u.id_casa_asignada', $casa->id_casa)
                ->where('u.estado', true)
                ->where('r.nombre_rol', 'Participante')
                ->select('u.id_usuario', 'u.nombre')
                ->orderBy('u.nombre')
                ->get();
        }

        return view('reuniones.crear', compact(
            'casas',
            'casa',
            'lider',
            'participantes'
        ));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'id_casa' => [
                'required',
                'integer',
                'exists:casa_iglesia,id_casa',
            ],
            'id_lider' => [
                'required',
                'integer',
                'exists:usuario,id_usuario',
            ],
            'fecha' => ['required', 'date'],
            'tema' => ['required', 'string', 'max:150'],
            'asistentes' => ['required', 'array', 'min:1'],
            'asistentes.*' => [
                'required',
                'integer',
                'distinct',
                'exists:usuario,id_usuario',
            ],
        ]);

        $idReunion = DB::transaction(function () use ($datos) {
            $casa = DB::table('casa_iglesia')
                ->where('id_casa', $datos['id_casa'])
                ->where('estado', true)
                ->first();

            $liderValido = $casa !== null
                && (int) $casa->id_lider_responsable === (int) $datos['id_lider']
                && DB::table('usuario as u')
                    ->join('rol as r', 'r.id_rol', '=', 'u.id_rol')
                    ->where('u.id_usuario', $datos['id_lider'])
                    ->where('u.estado', true)
                    ->where('r.nombre_rol', 'Líder')
                    ->exists();

            if (! $liderValido) {
                throw ValidationException::withMessages([
                    'id_lider' => 'El líder no está autorizado para esta casa.',
                ]);
            }

            $cantidadValidos = DB::table('usuario as u')
                ->join('rol as r', 'r.id_rol', '=', 'u.id_rol')
                ->whereIn('u.id_usuario', $datos['asistentes'])
                ->where('u.id_casa_asignada', $datos['id_casa'])
                ->where('u.estado', true)
                ->where('r.nombre_rol', 'Participante')
                ->count();

            if ($cantidadValidos !== count($datos['asistentes'])) {
                throw ValidationException::withMessages([
                    'asistentes' => 'Todos los asistentes deben ser participantes activos de la casa.',
                ]);
            }

            $idReunion = DB::table('reunion')->insertGetId([
                'id_casa' => $datos['id_casa'],
                'fecha' => $datos['fecha'],
                'tema' => $datos['tema'],
            ]);

            $filasAsistencia = array_map(
                fn ($idUsuario) => [
                    'id_reunion' => $idReunion,
                    'id_usuario' => $idUsuario,
                ],
                $datos['asistentes']
            );

            DB::table('asistencia')->insert($filasAsistencia);

            return $idReunion;
        });

        return response()->json([
            'id_reunion' => $idReunion,
            'asistentes_registrados' => count($datos['asistentes']),
        ], 201);
    }
}