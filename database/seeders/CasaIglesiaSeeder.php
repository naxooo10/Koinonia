<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CasaIglesiaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('casa_iglesia')->updateOrInsert(
            ['nombre_casa' => 'Casa Centro Demo'],
            [
                'direccion' => 'Dirección ficticia 100',
                'estado' => true,
                'id_lider_responsable' => null,
            ]
        );

        DB::table('casa_iglesia')->updateOrInsert(
            ['nombre_casa' => 'Casa Norte Demo'],
            [
                'direccion' => 'Dirección ficticia 200',
                'estado' => true,
                'id_lider_responsable' => null,
            ]
        );
    }
}