<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class NotificacionesWebTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/_pruebas/toast', function () {
            return response('<!doctype html><html><head><title>Prueba</title></head><body>Contenido</body></html>');
        });

        Route::middleware('web')->post('/casas', function (Request $request) {
            $request->validate(['nombre_casa' => ['required', 'string']]);
            return redirect('/_pruebas/toast');
        });

        Route::middleware('web')->post('/_pruebas/toast-json', function () {
            return response()->json(['id_reunion' => 123], 201);
        });

        Route::middleware('web')->get('/_pruebas/toast-prohibido', function () {
            abort(403, 'Acceso de prueba denegado.');
        });
    }

    public function test_el_html_incluye_los_avisos_de_sesion(): void
    {
        $this->withSession(['exito' => 'Guardado de prueba.'])
            ->get('/_pruebas/toast')
            ->assertOk()
            ->assertSee('koinonia-toast.css', false)
            ->assertSee('koinonia-toast.js', false)
            ->assertSee('Guardado de prueba.', false)
            ->assertSee('Contenido');
    }

    public function test_una_creacion_con_redireccion_guarda_confirmacion(): void
    {
        $usuario = new User();
        $usuario->setRawAttributes([
            '_id' => 999991,
            'nombre' => 'Usuario de prueba',
            'estado' => true,
            'id_rol' => 1,
        ]);

        $this->actingAs($usuario, 'web')
            ->post('/casas', ['nombre_casa' => 'Casa de prueba'])
            ->assertRedirect('/_pruebas/toast')
            ->assertSessionHas('exito', 'La Casa se creó correctamente.');
    }

    public function test_un_error_de_validacion_no_genera_confirmacion(): void
    {
        $this->postJson('/casas', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre_casa'])
            ->assertSessionMissing('exito')
            ->assertDontSee('koinonia-toast-data', false);
    }

    public function test_las_respuestas_json_conservan_su_contenido(): void
    {
        $this->postJson('/_pruebas/toast-json', [])
            ->assertCreated()
            ->assertExactJson(['id_reunion' => 123])
            ->assertDontSee('koinonia-toast-data', false);
    }

    public function test_un_error_html_conserva_el_estado_403(): void
    {
        $this->get('/_pruebas/toast-prohibido')
            ->assertForbidden()
            ->assertSee('koinonia-toast-data', false)
            ->assertSee('Acceso de prueba denegado.', false);
    }
}
