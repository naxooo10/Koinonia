<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Administrador', 'Líder', 'Participante'] as $nombre) {
            DB::table('rol')->updateOrInsert(
                ['nombre_rol' => $nombre],
                ['nombre_rol' => $nombre]
            );
        }
    }
}