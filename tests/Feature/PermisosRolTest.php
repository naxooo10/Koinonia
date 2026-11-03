<?php

namespace Tests\Feature;

use App\Http\Middleware\VerificarRol;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class PermisosRolTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware([
            'web',
            'auth',
            VerificarRol::class . ':Administrador',
        ])->get('/_pruebas/administrador', function () {
            return response()->json(['acceso' => true]);
        });

        Route::middleware([
            'web',
            'auth',
            VerificarRol::class . ':Líder',
        ])->get('/_pruebas/lider', function () {
            return response()->json(['acceso' => true]);
        });

        Route::middleware([
            'web',
            'auth',
            VerificarRol::class . ':Administrador,Líder,Participante',
        ])->get('/_pruebas/compartida', function () {
            return response()->json(['acceso' => true]);
        });
    }

    public function test_el_administrador_accede_solo_a_los_roles_permitidos(): void
    {
        $this->simularRol(1, 'Administrador');
        $this->actingAs($this->crearUsuario(1), 'web');

        $this->getJson('/_pruebas/administrador')
            ->assertOk()
            ->assertJson(['acceso' => true]);

        $this->getJson('/_pruebas/compartida')
            ->assertOk();

        $this->getJson('/_pruebas/lider')
            ->assertForbidden();
    }

    public function test_el_lider_accede_solo_a_los_roles_permitidos(): void
    {
        $this->simularRol(2, 'Líder');
        $this->actingAs($this->crearUsuario(2), 'web');

        $this->getJson('/_pruebas/lider')
            ->assertOk()
            ->assertJson(['acceso' => true]);

        $this->getJson('/_pruebas/compartida')
            ->assertOk();

        $this->getJson('/_pruebas/administrador')
            ->assertForbidden();
    }

    public function test_el_participante_no_accede_a_funciones_administrativas_o_de_lider(): void
    {
        $this->simularRol(3, 'Participante');
        $this->actingAs($this->crearUsuario(3), 'web');

        $this->getJson('/_pruebas/compartida')
            ->assertOk()
            ->assertJson(['acceso' => true]);

        $this->getJson('/_pruebas/administrador')
            ->assertForbidden();

        $this->getJson('/_pruebas/lider')
            ->assertForbidden();
    }

    public function test_una_cuenta_inactiva_recibe_401_en_json_y_cierra_sesion(): void
    {
        $this->actingAs($this->crearUsuario(1, false), 'web');

        $this->getJson('/_pruebas/administrador')
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Tu cuenta está inactiva.',
            ]);

        $this->assertGuest('web');
    }

    public function test_una_cuenta_inactiva_es_redirigida_al_login_en_html(): void
    {
        $this->actingAs($this->crearUsuario(1, false), 'web');

        $this->get('/_pruebas/administrador')
            ->assertRedirect('/login')
            ->assertSessionHasErrors([
                'email' => 'Tu cuenta está inactiva.',
            ]);

        $this->assertGuest('web');
    }

    public function test_un_rol_no_permitido_recibe_403(): void
    {
        $this->simularRol(99, 'Otro');
        $this->actingAs($this->crearUsuario(99), 'web');

        $this->getJson('/_pruebas/compartida')
            ->assertForbidden();
    }

    public function test_un_rol_inexistente_recibe_403(): void
    {
        $this->simularRol(99, null);
        $this->actingAs($this->crearUsuario(99), 'web');

        $this->getJson('/_pruebas/compartida')
            ->assertForbidden();
    }

    private function crearUsuario(
        int $idRol,
        bool $activo = true
    ): User {
        $usuario = new User();

        $usuario->setRawAttributes([
            '_id' => 900001,
            'nombre' => 'Usuario de prueba',
            'email' => 'prueba.roles@example.com',
            'estado' => $activo,
            'id_rol' => $idRol,
            'casas_ids' => [],
        ]);

        return $usuario;
    }

    private function simularRol(
        int $idRol,
        ?string $nombreRol
    ): void {
        $rol = $nombreRol === null
            ? null
            : (object) [
                'id' => $idRol,
                'nombre_rol' => $nombreRol,
            ];

        $consulta = Mockery::mock();

        $consulta->shouldReceive('where')
            ->with('_id', $idRol)
            ->andReturnSelf();

        $consulta->shouldReceive('first')
            ->andReturn($rol);

        $conexion = Mockery::mock();

        $conexion->shouldReceive('table')
            ->with('roles')
            ->andReturn($consulta);

        DB::shouldReceive('connection')
            ->with('mongodb')
            ->andReturn($conexion);
    }
}