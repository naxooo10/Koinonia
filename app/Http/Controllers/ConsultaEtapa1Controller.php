<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use MongoDB\Collection;

class ConsultaEtapa1Controller extends Controller
{
    public function casasActivas(): JsonResponse
    {
        $resultado = DB::connection('mongodb')
            ->table('casas_iglesia')
            ->where('estado', true)
            ->orderBy('nombre_casa')
            ->get()
            ->map(fn ($casa) => [
                'id_casa' => $casa->id,
                'nombre_casa' => $casa->nombre_casa,
                'direccion' => $casa->direccion,
            ])
            ->values();

        return response()->json($resultado);
    }

    public function lideresPorCasa(): JsonResponse
    {
        $etapas = [
            [
                '$match' => [
                    'estado' => true,
                ],
            ],
            [
                '$lookup' => [
                    'from' => 'usuarios',
                    'localField' => 'id_lider_responsable',
                    'foreignField' => '_id',
                    'as' => 'lider',
                ],
            ],
            [
                '$unwind' => '$lider',
            ],
            [
                '$sort' => [
                    'nombre_casa' => 1,
                    '_id' => 1,
                ],
            ],
            [
                '$project' => [
                    '_id' => 0,
                    'id_casa' => '$_id',
                    'nombre_casa' => 1,
                    'nombre_lider' => '$lider.nombre',
                ],
            ],
        ];

        $resultado = $this->ejecutarAgregacion(
            'casas_iglesia',
            $etapas
        );

        return response()->json($resultado);
    }

    public function asistentesPorReunion(): JsonResponse
    {
        $etapas = [
            [
                '$unwind' => '$asistencia',
            ],
            [
                '$match' => [
                    'asistencia.estado' => 'presente',
                ],
            ],
            [
                '$lookup' => [
                    'from' => 'casas_iglesia',
                    'localField' => 'id_casa',
                    'foreignField' => '_id',
                    'as' => 'casa',
                ],
            ],
            [
                '$unwind' => '$casa',
            ],
            [
                '$lookup' => [
                    'from' => 'usuarios',
                    'localField' => 'asistencia.usuario_id',
                    'foreignField' => '_id',
                    'as' => 'usuario',
                ],
            ],
            [
                '$unwind' => '$usuario',
            ],
            [
                '$sort' => [
                    'fecha' => 1,
                    '_id' => 1,
                    'usuario.nombre' => 1,
                ],
            ],
            [
                '$project' => [
                    '_id' => 0,
                    'id_reunion' => '$_id',
                    'fecha' => [
                        '$dateToString' => [
                            'format' => '%Y-%m-%d',
                            'date' => '$fecha',
                            'timezone' => 'UTC',
                        ],
                    ],
                    'tema' => 1,
                    'nombre_casa' => '$casa.nombre_casa',
                    'nombre_asistente' => '$usuario.nombre',
                ],
            ],
        ];

        $resultado = $this->ejecutarAgregacion(
            'reuniones',
            $etapas
        );

        return response()->json($resultado);
    }

    public function resumenPorCasa(): JsonResponse
    {
        $etapas = [
            [
                '$group' => [
                    '_id' => '$id_casa',
                    'cantidad_reuniones' => [
                        '$sum' => 1,
                    ],
                    'total_presentes' => [
                        '$sum' => [
                            '$size' => [
                                '$filter' => [
                                    'input' => [
                                        '$ifNull' => ['$asistencia', []],
                                    ],
                                    'as' => 'registro',
                                    'cond' => [
                                        '$eq' => [
                                            '$$registro.estado',
                                            'presente',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                '$lookup' => [
                    'from' => 'casas_iglesia',
                    'localField' => '_id',
                    'foreignField' => '_id',
                    'as' => 'casa',
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$casa',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ],
            [
                '$project' => [
                    '_id' => 0,
                    'id_casa' => '$_id',
                    'nombre_casa' => [
                        '$ifNull' => [
                            '$casa.nombre_casa',
                            'Casa no disponible',
                        ],
                    ],
                    'cantidad_reuniones' => 1,
                    'total_presentes' => 1,
                ],
            ],
            [
                '$sort' => [
                    'nombre_casa' => 1,
                    'id_casa' => 1,
                ],
            ],
        ];

        $resultado = $this->ejecutarAgregacion(
            'reuniones',
            $etapas
        );

        return response()->json($resultado);
    }

    private function ejecutarAgregacion(
        string $coleccion,
        array $etapas
    ): array {
        return DB::connection('mongodb')
            ->table($coleccion)
            ->raw(function (Collection $coleccion) use ($etapas) {
                return $coleccion->aggregate($etapas, [
                    'typeMap' => [
                        'root' => 'array',
                        'document' => 'array',
                        'array' => 'array',
                    ],
                ])->toArray();
            });
    }
}