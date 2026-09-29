<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InformeReunionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'mes' => ['required', 'integer', 'between:1,12'],
            'anio' => ['required', 'integer', 'between:2000,2100'],
            'id_lider' => ['nullable', 'integer', 'min:1'],
            'id_casa' => ['nullable', 'integer', 'min:1'],
            'id_participante' => ['nullable', 'integer', 'min:1'],
            'cantidad_asistentes' => ['nullable', 'integer', 'min:0'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'between' => 'El campo :attribute debe estar entre :min y :max.',
            'min' => 'El campo :attribute debe ser como mínimo :min.',
        ], [
            'mes' => 'mes',
            'anio' => 'año',
            'id_lider' => 'identificador del líder',
            'id_casa' => 'identificador de la casa',
            'id_participante' => 'identificador del participante',
            'cantidad_asistentes' => 'número de asistentes',
        ]);

        $inicio = Carbon::create(
            (int) $filtros['anio'],
            (int) $filtros['mes'],
            1
        );

        $fin = $inicio->copy()->addMonth();

        $consulta = DB::table('reunion as r')
            ->join('casa_iglesia as c', 'c.id_casa', '=', 'r.id_casa')
            ->leftJoin(
                'usuario as l',
                'l.id_usuario',
                '=',
                'c.id_lider_responsable'
            )
            ->select(
                'r.id_reunion',
                'r.fecha',
                'r.tema',
                'c.nombre_casa',
                'l.nombre as nombre_lider'
            )
            ->selectSub(function ($subconsulta) {
                $subconsulta->from('asistencia as a')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('a.id_reunion', 'r.id_reunion');
            }, 'cantidad_asistentes')
            ->where('r.fecha', '>=', $inicio->toDateString())
            ->where('r.fecha', '<', $fin->toDateString());

        if ($request->filled('id_lider')) {
            $consulta->where(
                'c.id_lider_responsable',
                $filtros['id_lider']
            );
        }

        if ($request->filled('id_casa')) {
            $consulta->where('r.id_casa', $filtros['id_casa']);
        }

        if ($request->filled('id_participante')) {
            $consulta->whereExists(function ($subconsulta) use ($filtros) {
                $subconsulta->selectRaw('1')
                    ->from('asistencia as ap')
                    ->whereColumn('ap.id_reunion', 'r.id_reunion')
                    ->where('ap.id_usuario', $filtros['id_participante']);
            });
        }

        if ($request->filled('cantidad_asistentes')) {
            $consulta->whereRaw(
                '(SELECT COUNT(*) FROM asistencia AS ac
                  WHERE ac.id_reunion = r.id_reunion) = ?',
                [(int) $filtros['cantidad_asistentes']]
            );
        }

        return response()->json(
            $consulta->orderBy('r.fecha')
                ->orderBy('r.id_reunion')
                ->get()
        );
    }
}