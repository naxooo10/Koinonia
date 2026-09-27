<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ConsultaEtapa1Controller extends Controller
{
    public function casasActivas(): JsonResponse
    {
        $resultado = DB::select(
            'SELECT id_casa, nombre_casa, direccion
             FROM casa_iglesia
             WHERE estado = 1
             ORDER BY nombre_casa'
        );

        return response()->json($resultado);
    }

    public function lideresPorCasa(): JsonResponse
    {
        $resultado = DB::select(
            'SELECT c.id_casa, c.nombre_casa, u.nombre AS nombre_lider
             FROM casa_iglesia AS c
             JOIN usuario AS u
               ON u.id_usuario = c.id_lider_responsable
             WHERE c.estado = 1
             ORDER BY c.nombre_casa'
        );

        return response()->json($resultado);
    }

    public function asistentesPorReunion(): JsonResponse
    {
        $resultado = DB::select(
            'SELECT r.id_reunion, r.fecha, r.tema,
                    c.nombre_casa, u.nombre AS nombre_asistente
             FROM reunion AS r
             JOIN casa_iglesia AS c
               ON c.id_casa = r.id_casa
             JOIN asistencia AS a
               ON a.id_reunion = r.id_reunion
             JOIN usuario AS u
               ON u.id_usuario = a.id_usuario
             ORDER BY r.fecha, r.id_reunion, u.nombre'
        );

        return response()->json($resultado);
    }
}