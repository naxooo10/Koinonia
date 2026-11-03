<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProteccionUsuariosTest extends TestCase
{
    private array $usuariosCreados = [];
    private array $casasCreadas = [];
    private array $rolesCreados = [];

    private User $administrador;
    private int $idAdministrador;
    private int $idLider;
    private int $idRolParticipante;

    protected function setUp(): void
    {
        parent::setUp();

        $idRolAdministrador = $this->obtenerOCrearRol('Administrador');
        $idRolLider = $this->obtenerOCrearRol('Líder');
        $this->idRolParticipante = $this->obtenerOCrearRol('Participante');

        $this->idAdministrador = random_int(100000000, 499999999);
        $this->idLider = random_int(500000000, 899999999);

        $datosAdministrador = [
            '_id' => $this->idAdministrador,
            'nombre' => 'Administrador de prueba',
            'email' => "admin.{$this->idAdministrador}@example.com",
            'estado' => true,
            'id_rol' => $idRolAdministrador,
            'casas_ids' => [],
        ];

        DB::connection('mongodb')
            ->table('usuarios')
            ->insert($datosAdministrador);

        $this->usuariosCreados[] = $this->idAdministrador;

        DB::connection('mongodb')
            ->table('usuarios')
            ->insert([
                '_id' => $this->idLider,
                'nombre' => 'Líder de prueba',
                'email' => "lider.{$this->idLider}@example.com",
                'estado' => true,
                'id_rol' => $idRolLider,
                'casas_ids' => [],
            ]);

        $this->usuariosCreados[] = $this->idLider;

        $this->administrador = new User();
        $this->administrador->setRawAttributes($datosAdministrador);

        $this->actingAs($this->administrador, 'web');
    }

    public function test_no_puedes_desactivar_tu_propia_cuenta(): void
    {
        $this->patchJson(
            "/usuarios/{$this->idAdministrador}/desactivar"
        )->assertForbidden()
            ->assertJson([
                'message' => 'No puedes desactivar tu propia cuenta.',
            ]);

        $usuario = $this->obtenerUsuario($this->idAdministrador);

        $this->assertTrue((bool) $usuario->estado);
    }

    public function test_no_puedes_quitarte_el_rol_de_administrador(): void
    {
        $this->patchJson(
            "/usuarios/{$this->idAdministrador}",
            [
                'nombre' => $this->administrador->nombre,
                'email' => $this->administrador->email,
                'id_rol' => $this->idRolParticipante,
            ]
        )->assertUnprocessable()
            ->assertJsonValidationErrors(['id_rol']);

        $usuario = $this->obtenerUsuario($this->idAdministrador);

        $this->assertSame(
            (int) $this->administrador->id_rol,
            (int) $usuario->id_rol
        );
    }

    public function test_no_puedes_desactivar_un_lider_con_casas_a_cargo(): void
    {
        $idCasa = random_int(100000000, 899999999);

        DB::connection('mongodb')
            ->table('casas_iglesia')
            ->insert([
                '_id' => $idCasa,
                'nombre_casa' => 'Casa de prueba de protección',
                'direccion' => 'Dirección de prueba',
                'estado' => true,
                'id_lider_responsable' => $this->idLider,
            ]);

        $this->casasCreadas[] = $idCasa;

        $this->patchJson(
            "/usuarios/{$this->idLider}/desactivar"
        )->assertForbidden()
            ->assertJson([
                'message' => 'Antes de desactivar este usuario, reasigna las Casas que están bajo su responsabilidad.',
            ]);

        $usuario = $this->obtenerUsuario($this->idLider);

        $this->assertTrue((bool) $usuario->estado);
    }

    public function test_puedes_desactivar_un_lider_sin_casas_a_cargo(): void
    {
        $this->patchJson(
            "/usuarios/{$this->idLider}/desactivar"
        )->assertRedirect('/panel/usuarios');

        $usuario = $this->obtenerUsuario($this->idLider);

        $this->assertFalse((bool) $usuario->estado);
    }

    protected function tearDown(): void
    {
        try {
            if (
                isset($this->app)
                && $this->app->environment('testing')
                && config('database.connections.mongodb.database')
                    === 'Koinonia_testing'
                && config('database.connections.mongodb.dsn')
                    === 'mongodb://127.0.0.1:27017'
            ) {
                if ($this->casasCreadas !== []) {
                    DB::connection('mongodb')
                        ->table('casas_iglesia')
                        ->whereIn('_id', $this->casasCreadas)
                        ->delete();
                }

                if ($this->usuariosCreados !== []) {
                    DB::connection('mongodb')
                        ->table('usuarios')
                        ->whereIn('_id', $this->usuariosCreados)
                        ->delete();
                }

                if ($this->rolesCreados !== []) {
                    DB::connection('mongodb')
                        ->table('roles')
                        ->whereIn('_id', $this->rolesCreados)
                        ->delete();
                }
            }
        } finally {
            parent::tearDown();
        }
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

    private function obtenerUsuario(int $id): object
    {
        $usuario = DB::connection('mongodb')
            ->table('usuarios')
            ->where('_id', $id)
            ->first();

        $this->assertNotNull($usuario);

        return $usuario;
    }
}