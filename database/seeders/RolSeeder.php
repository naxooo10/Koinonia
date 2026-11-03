<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use MongoDB\Collection;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            1 => 'Administrador',
            2 => 'Líder',
            3 => 'Participante',
        ];

        DB::connection('mongodb')
            ->table('roles')
            ->raw(function (Collection $coleccion) use ($roles): void {
                foreach ($roles as $id => $nombre) {
                    $coleccion->updateOne(
                        ['_id' => $id],
                        [
                            '$setOnInsert' => [
                                'nombre_rol' => $nombre,
                            ],
                        ],
                        ['upsert' => true]
                    );
                }
            });
    }
}