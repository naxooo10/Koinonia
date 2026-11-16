<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistroTest extends TestCase
{
    private array $correosCreados = [];
    private array $rolesCreados = [];

    private int $idRolParticipante;
    private int $idRolAdministrador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->idRolParticipante = $this->obtenerOCrearRol('Participante');
        $this->idRolAdministrador = $this->obtenerOCrearRol('Administrador');
    }

    protected function tearDown(): void
    {
        try {
            if ($this->correosCreados !== []) {
                DB::connection('mongodb')
                    ->table('usuarios')
                    ->whereIn('email', $this->correosCreados)
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

    public function test_el_formulario_de_registro_es_publico(): void
    {
        $this->get('/registro')
            ->assertOk()
            ->assertSee('Crear cuenta');
    }

    public function test_cualquier_persona_puede_crear_su_cuenta(): void
    {
        $correo = $this->correoNuevo();

        $this->post('/registro', [
            'nombre' => 'Persona nueva',
            'email' => $correo,
            'password' => 'ClaveNueva2',
            'password_confirmation' => 'ClaveNueva2',
        ])->assertRedirect('/login');

        $usuario = $this->obtenerPorCorreo($correo);

        $this->assertNotNull($usuario);
        $this->assertSame('Persona nueva', $usuario->nombre);
        $this->assertFalse((bool) $usuario->estado);
        $this->assertTrue((bool) $usuario->pendiente_aprobacion);
        $this->assertSame($this->idRolParticipante, (int) $usuario->id_rol);
        $this->assertSame([], (array) $usuario->casas_ids);
        $this->assertTrue(Hash::check('ClaveNueva2', $usuario->password));
        $this->assertGuest('web');
    }

    public function test_una_cuenta_pendiente_no_puede_iniciar_sesion(): void
    {
        $correo = $this->correoNuevo();

        $this->post('/registro', [
            'nombre' => 'Persona en espera',
            'email' => $correo,
            'password' => 'ClaveNueva2',
            'password_confirmation' => 'ClaveNueva2',
        ])->assertRedirect('/login');

        $this->postJson('/login', [
            'email' => $correo,
            'password' => 'ClaveNueva2',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest('web');
    }

    public function test_una_cuenta_aprobada_puede_iniciar_sesion(): void
    {
        $correo = $this->correoNuevo();

        $this->post('/registro', [
            'nombre' => 'Persona aprobada',
            'email' => $correo,
            'password' => 'ClaveNueva2',
            'password_confirmation' => 'ClaveNueva2',
        ])->assertRedirect('/login');

        // Equivale a lo que hace PATCH /usuarios/{id}/activar.
        DB::connection('mongodb')
            ->table('usuarios')
            ->where('email', $correo)
            ->update([
                'estado' => true,
                'pendiente_aprobacion' => false,
            ]);

        $this->post('/login', [
            'email' => $correo,
            'password' => 'ClaveNueva2',
        ])->assertRedirect('/panel');

        $this->assertAuthenticated('web');
    }

    public function test_el_registro_ignora_rol_estado_y_casas_enviados(): void
    {
        $correo = $this->correoNuevo();

        $this->post('/registro', [
            'nombre' => 'Persona curiosa',
            'email' => $correo,
            'password' => 'ClaveNueva2',
            'password_confirmation' => 'ClaveNueva2',
            'id_rol' => $this->idRolAdministrador,
            'estado' => true,
            'pendiente_aprobacion' => false,
            'casas_ids' => [1, 2],
        ])->assertRedirect('/login');

        $usuario = $this->obtenerPorCorreo($correo);

        $this->assertSame($this->idRolParticipante, (int) $usuario->id_rol);
        $this->assertFalse((bool) $usuario->estado);
        $this->assertTrue((bool) $usuario->pendiente_aprobacion);
        $this->assertSame([], (array) $usuario->casas_ids);
    }

    public function test_no_se_puede_registrar_un_correo_existente(): void
    {
        $correo = $this->correoNuevo();

        DB::connection('mongodb')->table('usuarios')->insert([
            '_id' => random_int(100000000, 899999999),
            'nombre' => 'Ya registrada',
            'email' => $correo,
            'password' => Hash::make('ClaveActual1'),
            'estado' => true,
            'id_rol' => $this->idRolParticipante,
            'casas_ids' => [],
        ]);

        $this->postJson('/registro', [
            'nombre' => 'Duplicada',
            'email' => $correo,
            'password' => 'ClaveNueva2',
            'password_confirmation' => 'ClaveNueva2',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertSame(
            1,
            DB::connection('mongodb')
                ->table('usuarios')
                ->where('email', $correo)
                ->count()
        );
    }

    public function test_el_registro_exige_la_politica_de_contrasenas(): void
    {
        $correo = $this->correoNuevo();

        foreach (['corta1', 'sololetras', '1234567890'] as $clave) {
            $this->postJson('/registro', [
                'nombre' => 'Persona nueva',
                'email' => $correo,
                'password' => $clave,
                'password_confirmation' => $clave,
            ])->assertUnprocessable()
                ->assertJsonValidationErrors('password');
        }

        $this->assertNull($this->obtenerPorCorreo($correo));
    }

    public function test_la_confirmacion_de_contrasena_debe_coincidir(): void
    {
        $correo = $this->correoNuevo();

        $this->postJson('/registro', [
            'nombre' => 'Persona nueva',
            'email' => $correo,
            'password' => 'ClaveNueva2',
            'password_confirmation' => 'OtraClave3',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertNull($this->obtenerPorCorreo($correo));
    }

    private function correoNuevo(): string
    {
        $correo = 'registro.' . random_int(100000000, 899999999) . '@example.com';

        $this->correosCreados[] = $correo;

        return $correo;
    }

    private function obtenerPorCorreo(string $correo): ?object
    {
        return DB::connection('mongodb')
            ->table('usuarios')
            ->where('email', $correo)
            ->first();
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
