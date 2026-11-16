<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PerfilTest extends TestCase
{
    private const CLAVE_ACTUAL = 'ClaveActual1';

    private array $usuariosCreados = [];
    private array $rolesCreados = [];

    private int $idUsuario;
    private int $idOtroUsuario;
    private int $idRolParticipante;

    protected function setUp(): void
    {
        parent::setUp();

        $this->idRolParticipante = $this->obtenerOCrearRol('Participante');

        $this->idUsuario = random_int(100000000, 499999999);
        $this->idOtroUsuario = random_int(500000000, 899999999);

        $datos = [
            '_id' => $this->idUsuario,
            'nombre' => 'Participante de prueba',
            'email' => "perfil.{$this->idUsuario}@example.com",
            'password' => Hash::make(self::CLAVE_ACTUAL),
            'estado' => true,
            'id_rol' => $this->idRolParticipante,
            'casas_ids' => [],
        ];

        DB::connection('mongodb')->table('usuarios')->insert($datos);
        $this->usuariosCreados[] = $this->idUsuario;

        DB::connection('mongodb')->table('usuarios')->insert([
            '_id' => $this->idOtroUsuario,
            'nombre' => 'Otra persona',
            'email' => "otra.{$this->idOtroUsuario}@example.com",
            'password' => Hash::make(self::CLAVE_ACTUAL),
            'estado' => true,
            'id_rol' => $this->idRolParticipante,
            'casas_ids' => [],
        ]);
        $this->usuariosCreados[] = $this->idOtroUsuario;

        $usuario = new User();
        $usuario->setRawAttributes($datos);

        $this->actingAs($usuario, 'web');
    }

    protected function tearDown(): void
    {
        try {
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
        } finally {
            parent::tearDown();
        }
    }

    public function test_el_perfil_muestra_los_datos_propios(): void
    {
        $this->get('/perfil')
            ->assertOk()
            ->assertSee('Participante de prueba')
            ->assertSee("perfil.{$this->idUsuario}@example.com");
    }

    public function test_un_invitado_no_puede_ver_el_perfil(): void
    {
        auth('web')->logout();

        $this->get('/perfil')->assertRedirect('/login');
    }

    public function test_puedo_actualizar_mi_nombre_y_correo(): void
    {
        $correo = "nuevo.{$this->idUsuario}@example.com";

        $this->patch('/perfil', [
            'nombre' => 'Nombre actualizado',
            'email' => $correo,
        ])->assertRedirect('/perfil');

        $usuario = $this->obtenerUsuario($this->idUsuario);

        $this->assertSame('Nombre actualizado', $usuario->nombre);
        $this->assertSame($correo, $usuario->email);
    }

    public function test_el_perfil_no_permite_cambiar_rol_ni_estado(): void
    {
        $this->patch('/perfil', [
            'nombre' => 'Participante de prueba',
            'email' => "perfil.{$this->idUsuario}@example.com",
            'id_rol' => 1,
            'estado' => false,
            'casas_ids' => [1, 2, 3],
        ])->assertRedirect('/perfil');

        $usuario = $this->obtenerUsuario($this->idUsuario);

        $this->assertSame($this->idRolParticipante, (int) $usuario->id_rol);
        $this->assertTrue((bool) $usuario->estado);
        $this->assertSame([], (array) $usuario->casas_ids);
    }

    public function test_no_puedo_usar_el_correo_de_otra_persona(): void
    {
        $this->patchJson('/perfil', [
            'nombre' => 'Participante de prueba',
            'email' => "otra.{$this->idOtroUsuario}@example.com",
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_puedo_cambiar_mi_contrasena(): void
    {
        $this->patch('/perfil/contrasena', [
            'password_actual' => self::CLAVE_ACTUAL,
            'password' => 'ClaveNueva2',
            'password_confirmation' => 'ClaveNueva2',
        ])->assertRedirect('/perfil');

        $usuario = $this->obtenerUsuario($this->idUsuario);

        $this->assertTrue(Hash::check('ClaveNueva2', $usuario->password));

        $otro = $this->obtenerUsuario($this->idOtroUsuario);

        $this->assertTrue(Hash::check(self::CLAVE_ACTUAL, $otro->password));
    }

    public function test_la_contrasena_actual_debe_ser_correcta(): void
    {
        $this->patchJson('/perfil/contrasena', [
            'password_actual' => 'Incorrecta9',
            'password' => 'ClaveNueva2',
            'password_confirmation' => 'ClaveNueva2',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('password_actual');

        $usuario = $this->obtenerUsuario($this->idUsuario);

        $this->assertTrue(Hash::check(self::CLAVE_ACTUAL, $usuario->password));
    }

    public function test_la_nueva_contrasena_debe_cumplir_la_politica(): void
    {
        foreach (['corta1', 'sololetras', '1234567890'] as $clave) {
            $this->patchJson('/perfil/contrasena', [
                'password_actual' => self::CLAVE_ACTUAL,
                'password' => $clave,
                'password_confirmation' => $clave,
            ])->assertUnprocessable()
                ->assertJsonValidationErrors('password');
        }
    }

    public function test_la_confirmacion_debe_coincidir(): void
    {
        $this->patchJson('/perfil/contrasena', [
            'password_actual' => self::CLAVE_ACTUAL,
            'password' => 'ClaveNueva2',
            'password_confirmation' => 'OtraClave3',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_las_respuestas_incluyen_cabeceras_de_seguridad(): void
    {
        $this->get('/perfil')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy');
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
