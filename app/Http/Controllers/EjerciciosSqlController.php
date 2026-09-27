<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class EjerciciosSqlController extends Controller
{
    public function ejecutar(): JsonResponse
    {
        // Preparación: tabla exclusiva de ejercicios.
        DB::statement(
            'CREATE OR REPLACE TABLE evidencia_casas (
                id_evidencia INT AUTO_INCREMENT PRIMARY KEY,
                nombre_casa VARCHAR(100) NOT NULL,
                estado TINYINT(1) NOT NULL DEFAULT 1
            )'
        );

        // 2 ALTER
        DB::statement(
            'ALTER TABLE evidencia_casas
             ADD COLUMN direccion VARCHAR(200) NULL'
        );

        DB::statement(
            'ALTER TABLE evidencia_casas
             MODIFY COLUMN nombre_casa VARCHAR(150) NOT NULL'
        );

        // 3 INSERT
        $insert1 = DB::affectingStatement(
            "INSERT INTO evidencia_casas (nombre_casa, direccion, estado)
             VALUES ('Casa Prueba A', 'Dirección ficticia A', 1)"
        );

        $insert2 = DB::affectingStatement(
            "INSERT INTO evidencia_casas (nombre_casa, direccion, estado)
             VALUES ('Casa Prueba B', 'Dirección ficticia B', 1)"
        );

        $insert3 = DB::affectingStatement(
            "INSERT INTO evidencia_casas (nombre_casa, direccion, estado)
             VALUES ('Casa Prueba C', 'Dirección ficticia C', 1)"
        );

        // 2 UPDATE
        $update1 = DB::affectingStatement(
            "UPDATE evidencia_casas
             SET direccion = 'Dirección ficticia A corregida'
             WHERE nombre_casa = 'Casa Prueba A'"
        );

        $update2 = DB::affectingStatement(
            "UPDATE evidencia_casas
             SET estado = 0
             WHERE nombre_casa = 'Casa Prueba B'"
        );

        // 2 DELETE
        $delete1 = DB::affectingStatement(
            "DELETE FROM evidencia_casas
             WHERE nombre_casa = 'Casa Prueba B'"
        );

        $delete2 = DB::affectingStatement(
            "DELETE FROM evidencia_casas
             WHERE nombre_casa = 'Casa Prueba C'"
        );

        // 1 DROP
        DB::statement('DROP TABLE evidencia_casas');

        return response()->json([
            'alter_ejecutados' => 2,
            'insert_filas' => [$insert1, $insert2, $insert3],
            'update_filas' => [$update1, $update2],
            'delete_filas' => [$delete1, $delete2],
            'tabla_de_ejercicios_eliminada' => true,
        ]);
    }
}