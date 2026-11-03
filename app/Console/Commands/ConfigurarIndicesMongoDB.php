<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use MongoDB\Collection;
use Throwable;

class ConfigurarIndicesMongoDB extends Command
{
    protected $signature = 'mongodb:indices';

    protected $description = 'Configura los índices de MongoDB para Koinonía';

    public function handle(): int
    {
        $indices = [
            [
                'coleccion' => 'usuarios',
                'campos' => ['email' => 1],
                'opciones' => [
                    'unique' => true,
                    'name' => 'usuarios_email_unico',
                ],
            ],
            [
                'coleccion' => 'roles',
                'campos' => ['nombre_rol' => 1],
                'opciones' => [
                    'unique' => true,
                    'name' => 'roles_nombre_unico',
                ],
            ],
            [
                'coleccion' => 'reuniones',
                'campos' => [
                    'fecha' => 1,
                    'id_casa' => 1,
                ],
                'opciones' => [
                    'name' => 'reuniones_fecha_casa',
                ],
            ],
            [
                'coleccion' => 'eventos',
                'campos' => ['fecha_hora' => 1],
                'opciones' => [
                    'name' => 'eventos_fecha_hora',
                ],
            ],
            [
                'coleccion' => 'materiales_estudio',
                'campos' => ['categoria' => 1],
                'opciones' => [
                    'name' => 'materiales_categoria',
                ],
            ],
        ];

        foreach ($indices as $indice) {
            $nombre = $indice['opciones']['name'];
            $coleccion = $indice['coleccion'];

            try {
                DB::connection('mongodb')
                    ->table($coleccion)
                    ->raw(function (Collection $documentos) use ($indice) {
                        return $documentos->createIndex(
                            $indice['campos'],
                            $indice['opciones']
                        );
                    });

                $this->info("Verificado: {$coleccion} → {$nombre}");
            } catch (Throwable $error) {
                $this->error(
                    "No se pudo configurar {$nombre} en {$coleccion}."
                );

                $this->error($error->getMessage());

                return self::FAILURE;
            }
        }

        $this->info('Los índices de MongoDB quedaron configurados.');

        return self::SUCCESS;
    }
}