<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use MongoDB\Collection;

class CasaIglesiaSeeder extends Seeder
{
    public function run(): void
    {
        $casas = [
            [
                '_id' => 1,
                'nombre_casa' => 'Casa Iglesia Quilpue',
                'direccion' => 'Los Aromos 123, Quilpue, Chile',
                'estado' => true,
                'id_lider_responsable' => null,
            ],
            [
                '_id' => 4,
                'nombre_casa' => 'Casa Iglesia Viña del Mar',
                'direccion' => 'Av. Libertad 450, Viña del Mar, Chile',
                'estado' => true,
                'id_lider_responsable' => null,
            ],
        ];

        DB::connection('mongodb')
            ->table('casas_iglesia')
            ->raw(function (Collection $coleccion) use ($casas): void {
                foreach ($casas as $casa) {
                    $id = $casa['_id'];

                    unset($casa['_id']);

                    $coleccion->updateOne(
                        ['_id' => $id],
                        ['$setOnInsert' => $casa],
                        ['upsert' => true]
                    );
                }
            });
    }
}