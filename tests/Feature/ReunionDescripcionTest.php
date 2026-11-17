<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * El líder registra una reunión con título, descripción y asistencia.
 * El motivo de ausencia solo se conserva para quien queda ausente.
 */
class ReunionDescripcionTest extends TestCase
{
    private array $usuariosCreados = [];
    private array $casasCreadas = [];
    private array $reunionesCreadas = [];
    private array $rolesCreados = [];

    private int $idLider;
    private int $idCasa;
    private int $idPresente;
    private int $idAusente;

    protected function setUp(): void
    {
        parent::setUp();

        $idRolLider = $this->obtenerOCrearRol('Líder');
        $idRolParticipante = $this->obtenerOCrearRol('Participante');

        $this->idLider = random_int(100000000, 299999999);
        $this->idPresente = random_int(300000000, 499999999);
        $this->idAusente = random_int(500000000, 699999999);
        $this->idCasa = random_int(700000000, 899999999);

        $datosLider = [
            '_id' => $this->idLider,
            'nombre' => 'Líder de prueba',
            'email' => "lider.{$this->idLider}@example.com",
            'estado' => true,
            'id_rol' => $idRolLider,
            'casas_ids' => [],
        ];

        DB::connection('mongodb')->table('usuarios')->insert($datosLider);
        $this->usuariosCreados[] = $this->idLider;

        foreach ([$this->idPresente, $this->idAusente] as $id) {
            DB::connection('mongodb')->table('usuarios')->insert([
                '_id' => $id,
                'nombre' => "Integrante {$id}",
                'email' => "integrante.{$id}@example.com",
                'estado' => true,
                'id_rol' => $idRolParticipante,
                'casas_ids' => [$this->idCasa],
            ]);

            $this->usuariosCreados[] = $id;
        }

        DB::connection('mongodb')->table('casas_iglesia')->insert([
            '_id' => $this->idCasa,
            'nombre_casa' => "Casa de prueba {$this->idCasa}",
            'direccion' => 'Dirección ficticia 123',
            'estado' => true,
            'id_lider_responsable' => $this->idLider,
        ]);

        $this->casasCreadas[] = $this->idCasa;

        $lider = new User();
        $lider->setRawAttributes($datosLider);

        $this->actingAs($lider, 'web');
    }

    protected function tearDown(): void
    {
        try {
            $conexion = DB::connection('mongodb');

            $conexion->table('reuniones')
                ->whereIn('id_casa', $this->casasCreadas)
                ->delete();

            $conexion->table('casas_iglesia')
                ->whereIn('_id', $this->casasCreadas)
                ->delete();

            $conexion->table('usuarios')
                ->whereIn('_id', $this->usuariosCreados)
                ->delete();

            if ($this->rolesCreados !== []) {
                $conexion->table('roles')
                    ->whereIn('_id', $this->rolesCreados)
                    ->delete();
            }
        } finally {
            parent::tearDown();
        }
    }

    public function test_el_lider_registra_titulo_descripcion_y_asistencia(): void
    {
        $respuesta = $this->postJson('/reuniones', $this->datosReunion([
            'descripcion' => '  Estudiamos Filipenses 4 y oramos por las familias.  ',
        ]));

        $respuesta->assertCreated()
            ->assertJson([
                'asistentes_registrados' => 1,
                'ausentes' => 1,
                'pendientes' => 0,
            ]);

        $reunion = $this->obtenerReunion((int) $respuesta->json('id_reunion'));

        $this->assertSame('Vivir en gratitud', $reunion->tema);
        $this->assertSame(
            'Estudiamos Filipenses 4 y oramos por las familias.',
            $reunion->descripcion
        );
    }

    public function test_la_descripcion_es_opcional(): void
    {
        $respuesta = $this->postJson('/reuniones', $this->datosReunion());

        $respuesta->assertCreated();

        $reunion = $this->obtenerReunion((int) $respuesta->json('id_reunion'));

        $this->assertNull($reunion->descripcion);
    }

    public function test_la_descripcion_tiene_un_largo_maximo(): void
    {
        $this->postJson('/reuniones', $this->datosReunion([
            'descripcion' => str_repeat('a', 3001),
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors('descripcion');
    }

    public function test_el_motivo_solo_se_guarda_para_quien_esta_ausente(): void
    {
        $respuesta = $this->postJson('/reuniones', $this->datosReunion());

        $respuesta->assertCreated();

        $asistencia = $this->asistenciaPorUsuario(
            (int) $respuesta->json('id_reunion')
        );

        $this->assertSame('presente', $asistencia[$this->idPresente]['estado']);
        $this->assertNull($asistencia[$this->idPresente]['justificacion']);

        $this->assertSame('ausente', $asistencia[$this->idAusente]['estado']);
        $this->assertSame(
            'Estaba de viaje por trabajo.',
            $asistencia[$this->idAusente]['justificacion']
        );
    }

    public function test_el_lider_puede_corregir_la_descripcion_y_el_motivo(): void
    {
        $idReunion = (int) $this->postJson('/reuniones', $this->datosReunion())
            ->assertCreated()
            ->json('id_reunion');

        $this->patchJson("/reuniones/{$idReunion}", [
            'fecha' => '2026-11-19',
            'tema' => 'Vivir en gratitud',
            'descripcion' => 'Descripción corregida.',
            'asistencia' => [
                [
                    'usuario_id' => $this->idPresente,
                    'estado' => 'ausente',
                    'justificacion' => 'Se enfermó.',
                ],
                [
                    'usuario_id' => $this->idAusente,
                    'estado' => 'presente',
                    'justificacion' => 'Estaba de viaje por trabajo.',
                ],
            ],
        ])->assertOk();

        $reunion = $this->obtenerReunion($idReunion);

        $this->assertSame('Descripción corregida.', $reunion->descripcion);

        $asistencia = $this->asistenciaPorUsuario($idReunion);

        $this->assertSame('Se enfermó.', $asistencia[$this->idPresente]['justificacion']);
        $this->assertNull($asistencia[$this->idAusente]['justificacion']);
    }

    public function test_los_formularios_muestran_los_campos_nuevos(): void
    {
        $this->get("/reuniones/crear?id_casa={$this->idCasa}")
            ->assertOk()
            ->assertSee('Título de la reunión')
            ->assertSee('Descripción de la reunión')
            ->assertSee('Motivo de ausencia');

        $idReunion = (int) $this->postJson('/reuniones', $this->datosReunion([
            'descripcion' => 'Texto visible al editar.',
        ]))->assertCreated()->json('id_reunion');

        $this->get("/reuniones/{$idReunion}/editar")
            ->assertOk()
            ->assertSee('Descripción de la reunión')
            ->assertSee('Texto visible al editar.');
    }

    private function datosReunion(array $extra = []): array
    {
        return array_merge([
            'id_casa' => $this->idCasa,
            'fecha' => '2026-11-19',
            'tema' => 'Vivir en gratitud',
            'asistencia' => [
                [
                    'usuario_id' => $this->idPresente,
                    'estado' => 'presente',
                    'justificacion' => 'Este texto no debe guardarse.',
                ],
                [
                    'usuario_id' => $this->idAusente,
                    'estado' => 'ausente',
                    'justificacion' => 'Estaba de viaje por trabajo.',
                ],
            ],
        ], $extra);
    }

    private function obtenerReunion(int $id): object
    {
        $reunion = DB::connection('mongodb')
            ->table('reuniones')
            ->where('_id', $id)
            ->first();

        $this->assertNotNull($reunion);

        return $reunion;
    }

    private function asistenciaPorUsuario(int $idReunion): array
    {
        $reunion = $this->obtenerReunion($idReunion);
        $porUsuario = [];

        foreach ((array) $reunion->asistencia as $registro) {
            $registro = (array) $registro;
            $porUsuario[(int) $registro['usuario_id']] = $registro;
        }

        return $porUsuario;
    }

    private function obtenerOCrearRol(string $nombre): int
    {
        $rol = DB::connection('mongodb')
            ->table('roles')
            ->where('nombre_rol', $nombre)
            ->first();

        if ($rol !== null) {
            return (int) $rol->id;
        }

        $id = random_int(100000000, 899999999);

        DB::connection('mongodb')
            ->table('roles')
            ->insert([
                '_id' => $id,
                'nombre_rol' => $nombre,
            ]);

        $this->rolesCreados[] = $id;

        return $id;
    }
}
