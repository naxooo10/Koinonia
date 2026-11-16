<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Al cambiar la contraseña, las sesiones abiertas con la contraseña
 * anterior deben cerrarse y la sesión que hizo el cambio debe seguir.
 *
 * Cada petición real crea un guard nuevo que lee al usuario desde la
 * base; en las pruebas el guard se conserva entre peticiones, por eso
 * se llama a forgetGuards() para reproducir ese comportamiento.
 */
class CierreDeSesionesTest extends TestCase
{
    private const CLAVE_ACTUAL = 'ClaveActual1';
    private const CLAVE_NUEVA = 'ClaveNueva2';

    private array $usuariosCreados = [];
    private array $rolesCreados = [];

    private int $idUsuario;
    private string $correo;

    protected function setUp(): void
    {
        parent::setUp();

        $idRol = $this->obtenerOCrearRol('Participante');

        $this->idUsuario = random_int(100000000, 899999999);
        $this->correo = "sesiones.{$this->idUsuario}@example.com";

        DB::connection('mongodb')->table('usuarios')->insert([
            '_id' => $this->idUsuario,
            'nombre' => 'Persona con dos dispositivos',
            'email' => $this->correo,
            'password' => Hash::make(self::CLAVE_ACTUAL),
            'estado' => true,
            'id_rol' => $idRol,
            'casas_ids' => [],
        ]);

        $this->usuariosCreados[] = $this->idUsuario;
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

    public function test_la_sesion_sigue_activa_mientras_la_contrasena_no_cambia(): void
    {
        $this->iniciarSesion();

        $this->nuevaPeticion();
        $this->get('/perfil')->assertOk();

        $this->nuevaPeticion();
        $this->get('/perfil')->assertOk();
    }

    public function test_una_sesion_abierta_se_cierra_si_la_contrasena_cambia_en_otro_dispositivo(): void
    {
        $this->iniciarSesion();

        $this->nuevaPeticion();
        $this->get('/perfil')->assertOk();

        // Otro dispositivo cambia la contraseña de la cuenta.
        DB::connection('mongodb')
            ->table('usuarios')
            ->where('_id', $this->idUsuario)
            ->update(['password' => Hash::make(self::CLAVE_NUEVA)]);

        $this->nuevaPeticion();
        $this->get('/perfil')->assertRedirect('/login');

        $this->nuevaPeticion();
        $this->assertGuest('web');
    }

    public function test_el_dispositivo_que_cambia_la_contrasena_conserva_su_sesion(): void
    {
        $this->iniciarSesion();

        $this->nuevaPeticion();
        $this->patch('/perfil/contrasena', [
            'password_actual' => self::CLAVE_ACTUAL,
            'password' => self::CLAVE_NUEVA,
            'password_confirmation' => self::CLAVE_NUEVA,
        ])->assertRedirect('/perfil');

        $usuario = DB::connection('mongodb')
            ->table('usuarios')
            ->where('_id', $this->idUsuario)
            ->first();

        $this->assertTrue(Hash::check(self::CLAVE_NUEVA, $usuario->password));

        $this->nuevaPeticion();
        $this->get('/perfil')->assertOk();

        $this->nuevaPeticion();
        $this->assertAuthenticated('web');
    }

    private function iniciarSesion(): void
    {
        $this->post('/login', [
            'email' => $this->correo,
            'password' => self::CLAVE_ACTUAL,
        ])->assertRedirect('/panel');
    }

    private function nuevaPeticion(): void
    {
        $this->app['auth']->forgetGuards();
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
