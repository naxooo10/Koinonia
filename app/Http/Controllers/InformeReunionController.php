<?php

namespace App\Http\Controllers;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;

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
            'id_casa' => 'identificador de la Casa',
            'id_participante' => 'identificador del participante',
            'cantidad_asistentes' => 'número de asistentes',
        ]);

        $inicio = new DateTimeImmutable(
            sprintf(
                '%04d-%02d-01 00:00:00',
                (int) $filtros['anio'],
                (int) $filtros['mes']
            ),
            new DateTimeZone('UTC')
        );

        $fin = $inicio->modify('+1 month');

        $condiciones = [
            'fecha' => [
                '$gte' => new UTCDateTime($inicio),
                '$lt' => new UTCDateTime($fin),
            ],
        ];

        if ($request->filled('id_casa')) {
            $condiciones['id_casa'] = (int) $filtros['id_casa'];
        }

        if ($request->filled('id_participante')) {
            $condiciones['asistencia.usuario_id'] =
                (int) $filtros['id_participante'];
        }

        $etapas = [
            [
                '$match' => $condiciones,
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
                '$unwind' => [
                    'path' => '$casa',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ],
        ];

        if ($request->filled('id_lider')) {
            $etapas[] = [
                '$match' => [
                    'casa.id_lider_responsable' =>
                        (int) $filtros['id_lider'],
                ],
            ];
        }

        $conteos = [];

        foreach ([
            'cantidad_asistentes' => 'presente',
            'cantidad_ausentes' => 'ausente',
            'cantidad_pendientes' => 'pendiente',
        ] as $campo => $estado) {
            $conteos[$campo] = [
                '$size' => [
                    '$filter' => [
                        'input' => [
                            '$ifNull' => ['$asistencia', []],
                        ],
                        'as' => 'registro',
                        'cond' => [
                            '$eq' => ['$$registro.estado', $estado],
                        ],
                    ],
                ],
            ];
        }

        $etapas[] = [
            '$set' => $conteos,
        ];

        if ($request->filled('cantidad_asistentes')) {
            $etapas[] = [
                '$match' => [
                    'cantidad_asistentes' =>
                        (int) $filtros['cantidad_asistentes'],
                ],
            ];
        }

        $etapas[] = [
            '$lookup' => [
                'from' => 'usuarios',
                'localField' => 'casa.id_lider_responsable',
                'foreignField' => '_id',
                'pipeline' => [
                    [
                        '$project' => [
                            '_id' => 1,
                            'nombre' => 1,
                        ],
                    ],
                ],
                'as' => 'lider',
            ],
        ];

        $etapas[] = [
            '$lookup' => [
                'from' => 'usuarios',
                'localField' => 'asistencia.usuario_id',
                'foreignField' => '_id',
                'pipeline' => [
                    [
                        '$project' => [
                            '_id' => 1,
                            'nombre' => 1,
                        ],
                    ],
                ],
                'as' => 'personas',
            ],
        ];

        $etapas[] = [
            '$sort' => [
                'fecha' => 1,
                '_id' => 1,
            ],
        ];

        $etapas[] = [
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
                'descripcion' => [
                    '$ifNull' => ['$descripcion', null],
                ],
                'id_casa' => 1,
                'nombre_casa' => [
                    '$ifNull' => [
                        '$casa.nombre_casa',
                        'Casa no disponible',
                    ],
                ],
                'id_lider' => [
                    '$ifNull' => [
                        '$casa.id_lider_responsable',
                        null,
                    ],
                ],
                'nombre_lider' => [
                    '$ifNull' => [
                        [
                            '$arrayElemAt' => ['$lider.nombre', 0],
                        ],
                        'Sin líder asignado',
                    ],
                ],
                'cantidad_asistentes' => 1,
                'cantidad_ausentes' => 1,
                'cantidad_pendientes' => 1,
                'asistencia' => [
                    '$ifNull' => ['$asistencia', []],
                ],
                'personas' => 1,
            ],
        ];

        $resultados = DB::connection('mongodb')
            ->table('reuniones')
            ->raw(function (Collection $coleccion) use ($etapas) {
                return $coleccion->aggregate($etapas, [
                    'typeMap' => [
                        'root' => 'array',
                        'document' => 'array',
                        'array' => 'array',
                    ],
                ])->toArray();
            });

        $reuniones = collect($resultados)
            ->map(function (array $reunion) {
                $personas = collect($reunion['personas'])
                    ->keyBy('_id');

                $reunion['asistencia'] = collect($reunion['asistencia'])
                    ->map(function (array $registro) use ($personas) {
                        $persona = $personas->get(
                            $registro['usuario_id']
                        );

                        return [
                            'usuario_id' => $registro['usuario_id'],
                            'nombre' =>
                                $persona['nombre'] ?? 'Usuario no disponible',
                            'estado' => $registro['estado'],
                            'justificacion' =>
                                $registro['justificacion'] ?? null,
                        ];
                    })
                    ->values()
                    ->all();

                unset($reunion['personas']);

                return $reunion;
            })
            ->values();

        return response()->json($reuniones);
    }
}