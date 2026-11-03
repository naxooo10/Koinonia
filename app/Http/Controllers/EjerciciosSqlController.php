<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use MongoDB\Collection;
use MongoDB\Driver\Command;

class EjerciciosSqlController extends Controller
{
    public function ejecutar(): JsonResponse
    {
        $nombreColeccion = 'evidencia_casas_' . bin2hex(random_bytes(8));

        $resultado = DB::connection('mongodb')
            ->table($nombreColeccion)
            ->raw(function (Collection $coleccion) use ($nombreColeccion) {
                $gestor = $coleccion->getManager();
                $base = $coleccion->getDatabaseName();

                $esquema = [
                    'bsonType' => 'object',
                    'required' => [
                        '_id',
                        'nombre_casa',
                        'estado',
                    ],
                    'additionalProperties' => false,
                    'properties' => [
                        '_id' => [
                            'bsonType' => ['int', 'long'],
                        ],
                        'nombre_casa' => [
                            'bsonType' => 'string',
                            'maxLength' => 100,
                        ],
                        'estado' => [
                            'bsonType' => 'bool',
                        ],
                    ],
                ];

                $gestor->executeCommand($base, new Command([
                    'create' => $nombreColeccion,
                    'validator' => [
                        '$jsonSchema' => $esquema,
                    ],
                    'validationLevel' => 'strict',
                    'validationAction' => 'error',
                ]));

                try {
                    $esquema['properties']['direccion'] = [
                        'bsonType' => ['string', 'null'],
                        'maxLength' => 200,
                    ];

                    $gestor->executeCommand($base, new Command([
                        'collMod' => $nombreColeccion,
                        'validator' => [
                            '$jsonSchema' => $esquema,
                        ],
                    ]));

                    $esquema['properties']['nombre_casa']['maxLength'] = 150;

                    $gestor->executeCommand($base, new Command([
                        'collMod' => $nombreColeccion,
                        'validator' => [
                            '$jsonSchema' => $esquema,
                        ],
                    ]));

                    $insert1 = $coleccion->insertOne([
                        '_id' => 1,
                        'nombre_casa' => 'Casa Prueba A',
                        'direccion' => 'Dirección ficticia A',
                        'estado' => true,
                    ]);

                    $insert2 = $coleccion->insertOne([
                        '_id' => 2,
                        'nombre_casa' => 'Casa Prueba B',
                        'direccion' => 'Dirección ficticia B',
                        'estado' => true,
                    ]);

                    $insert3 = $coleccion->insertOne([
                        '_id' => 3,
                        'nombre_casa' => 'Casa Prueba C',
                        'direccion' => 'Dirección ficticia C',
                        'estado' => true,
                    ]);

                    $update1 = $coleccion->updateOne(
                        ['_id' => 1],
                        [
                            '$set' => [
                                'direccion' =>
                                    'Dirección ficticia A corregida',
                            ],
                        ]
                    );

                    $update2 = $coleccion->updateOne(
                        ['_id' => 2],
                        [
                            '$set' => [
                                'estado' => false,
                            ],
                        ]
                    );

                    $opciones = [
                        'sort' => ['_id' => 1],
                        'typeMap' => [
                            'root' => 'array',
                            'document' => 'array',
                            'array' => 'array',
                        ],
                    ];

                    $antesDeEliminar = $coleccion
                        ->find([], $opciones)
                        ->toArray();

                    $delete1 = $coleccion->deleteOne([
                        '_id' => 2,
                    ]);

                    $delete2 = $coleccion->deleteOne([
                        '_id' => 3,
                    ]);

                    $despuesDeEliminar = $coleccion
                        ->find([], $opciones)
                        ->toArray();

                    $datos = [
                        'motor' => 'MongoDB',
                        'coleccion_temporal' => $nombreColeccion,
                        'cambios_validacion_ejecutados' => 2,
                        'insert_documentos' => [
                            $insert1->getInsertedCount(),
                            $insert2->getInsertedCount(),
                            $insert3->getInsertedCount(),
                        ],
                        'update_documentos' => [
                            $update1->getModifiedCount(),
                            $update2->getModifiedCount(),
                        ],
                        'delete_documentos' => [
                            $delete1->getDeletedCount(),
                            $delete2->getDeletedCount(),
                        ],
                        'antes_de_eliminar' => $antesDeEliminar,
                        'despues_de_eliminar' => $despuesDeEliminar,
                    ];
                } finally {
                    $coleccion->drop();
                }

                $datos['coleccion_temporal_eliminada'] = true;

                return $datos;
            });

        return response()->json($resultado);
    }
}