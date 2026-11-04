<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use MongoDB\Collection;

/**
 * Crea las cuentas iniciales (una por rol) para poder iniciar sesión
 * en una instalación nueva. Solo un Administrador puede crear usuarios
 * desde la aplicación, por lo que al menos uno debe existir de antemano.
 *
 * La contraseña se toma de SEED_PASSWORD. En local, si no está definida,
 * se usa "password". En producción no se crean cuentas sin ella, para no
 * dejar un administrador con una clave conocida en la app publicada.
 * Si el correo ya existe, el usuario no se modifica.
 */
class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $claveConfigurada = config('app.seed_password');

        if (blank($claveConfigurada) && app()->environment('production')) {
            $this->command?->warn(
                'UsuarioSeeder omitido: define SEED_PASSWORD para crear las cuentas iniciales en producción.'
            );

            return;
        }

        $clave = Hash::make($claveConfigurada ?: 'password');

        $usuarios = [
            [
                'nombre' => 'Administrador Koinonía',
                'email' => 'admin@koinonia.test',
                'id_rol' => 1,
                'casas_ids' => [],
            ],
            [
                'nombre' => 'Líder Koinonía',
                'email' => 'lider@koinonia.test',
                'id_rol' => 2,
                'casas_ids' => [1],
            ],
            [
                'nombre' => 'Participante Koinonía',
                'email' => 'participante@koinonia.test',
                'id_rol' => 3,
                'casas_ids' => [1],
            ],
        ];

        DB::connection('mongodb')
            ->table('usuarios')
            ->raw(function (Collection $coleccion) use ($usuarios, $clave): void {
                foreach ($usuarios as $usuario) {
                    if ($coleccion->countDocuments(['email' => $usuario['email']]) > 0) {
                        continue;
                    }

                    $ultimo = $coleccion->findOne([], ['sort' => ['_id' => -1]]);
                    $siguienteId = $ultimo ? ((int) $ultimo['_id']) + 1 : 1;

                    $coleccion->insertOne([
                        '_id' => $siguienteId,
                        ...$usuario,
                        'password' => $clave,
                        'estado' => true,
                    ]);
                }
            });
    }
}
