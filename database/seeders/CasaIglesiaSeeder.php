<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CasaIglesiaSeeder extends Seeder
{
    public function run(): void
    {
        $casas = [
            ['nombre_casa' => 'Casa Centro Demo', 'direccion' => 'Dirección ficticia 100'],
            ['nombre_casa' => 'Casa Norte Demo', 'direccion' => 'Dirección ficticia 200'],
        ];

        foreach ($casas as $casa) {
            $existe = DB::table('casa_iglesia')
                ->where('nombre_casa', $casa['nombre_casa'])
                ->exists();

            if (! $existe) {
                DB::table('casa_iglesia')->insert([
                    ...$casa,
                    'estado' => true,
                    'id_lider_responsable' => null,
                ]);
            }
        }
    }
}