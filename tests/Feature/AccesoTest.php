<?php

namespace Tests\Feature;

use Tests\TestCase;

class AccesoTest extends TestCase
{
    public function test_el_formulario_de_login_esta_disponible(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Iniciar sesión');
    }

    public function test_un_visitante_es_redirigido_al_login(): void
    {
        $rutas = [
            '/panel',
            '/panel/casas',
            '/panel/usuarios',
            '/panel/reuniones',
            '/panel/consultas',
            '/casas',
            '/usuarios',
            '/reuniones',
            '/reuniones/crear',
            '/eventos',
            '/materiales',
        ];

        foreach ($rutas as $ruta) {
            $this->get($ruta)
                ->assertRedirect('/login');
        }
    }

    public function test_las_consultas_json_requieren_autenticacion(): void
    {
        $rutas = [
            '/roles',
            '/casas',
            '/usuarios',
            '/informes/reuniones',
            '/etapa1/consultas/1',
            '/etapa1/consultas/2',
            '/etapa1/consultas/3',
            '/etapa1/consultas/4',
        ];

        foreach ($rutas as $ruta) {
            $this->getJson($ruta)
                ->assertUnauthorized();
        }
    }
}