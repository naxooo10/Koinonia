<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CasaIglesiaController;
use App\Http\Controllers\ConsultaEtapa1Controller;
use App\Http\Controllers\EjerciciosSqlController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\InformeReunionController;
use App\Http\Controllers\MaterialEstudioController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\ReunionController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/login'));

Route::get('/salud', function () {
    DB::connection('mongodb')
        ->table('roles')
        ->first();

    return response()->json([
        'aplicacion' => 'Koinonía',
        'motor' => 'MongoDB',
        'base_de_datos' => 'conectada',
    ]);
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    Route::middleware('rol:Administrador,Líder,Participante')
        ->group(function () {
            Route::get('/panel', [
                PanelController::class,
                'index',
            ])->name('dashboard');

            Route::get('/eventos', [
                EventoController::class,
                'index',
            ]);
        });

    Route::middleware('rol:Administrador,Líder')->group(function () {
        Route::get('/eventos/crear', [
            EventoController::class,
            'create',
        ]);

        Route::post('/eventos', [
            EventoController::class,
            'store',
        ]);

        Route::get('/eventos/{id}/editar', [
            EventoController::class,
            'edit',
        ])->whereNumber('id');

        Route::patch('/eventos/{id}', [
            EventoController::class,
            'update',
        ])->whereNumber('id');

        Route::delete('/eventos/{id}', [
            EventoController::class,
            'destroy',
        ])->whereNumber('id');

        Route::get('/materiales', [
            MaterialEstudioController::class,
            'index',
        ]);

        Route::get('/materiales/{id}/descargar', [
            MaterialEstudioController::class,
            'download',
        ])->whereNumber('id');
    });

    Route::middleware('rol:Administrador')->group(function () {
        Route::view('/panel/casas', 'panel.casas');
        Route::view('/panel/usuarios', 'panel.usuarios');
        Route::view('/panel/reuniones', 'panel.reuniones');
        Route::view('/panel/consultas', 'panel.consultas');

        Route::get('/roles', [
            RolController::class,
            'index',
        ]);

        Route::get('/casas', [
            CasaIglesiaController::class,
            'index',
        ]);

        Route::get('/casas/crear', [
            CasaIglesiaController::class,
            'create',
        ]);

        Route::post('/casas', [
            CasaIglesiaController::class,
            'store',
        ]);

        Route::get('/casas/{id}/editar', [
            CasaIglesiaController::class,
            'edit',
        ]);

        Route::patch('/casas/{id}', [
            CasaIglesiaController::class,
            'update',
        ]);

        Route::patch('/casas/{id}/desactivar', [
            CasaIglesiaController::class,
            'deactivate',
        ]);

        Route::patch('/casas/{id}/activar', [
            CasaIglesiaController::class,
            'activate',
        ]);

        Route::get('/usuarios', [
            UsuarioController::class,
            'index',
        ]);

        Route::get('/usuarios/crear', [
            UsuarioController::class,
            'create',
        ]);

        Route::post('/usuarios', [
            UsuarioController::class,
            'store',
        ]);

        Route::get('/usuarios/{id}/editar', [
            UsuarioController::class,
            'edit',
        ]);

        Route::patch('/usuarios/{id}', [
            UsuarioController::class,
            'update',
        ]);

        Route::patch('/usuarios/{id}/desactivar', [
            UsuarioController::class,
            'deactivate',
        ]);

        Route::patch('/usuarios/{id}/activar', [
            UsuarioController::class,
            'activate',
        ]);

        Route::get('/materiales/crear', [
            MaterialEstudioController::class,
            'create',
        ]);

        Route::post('/materiales', [
            MaterialEstudioController::class,
            'store',
        ]);

        Route::get('/materiales/{id}/editar', [
            MaterialEstudioController::class,
            'edit',
        ])->whereNumber('id');

        Route::patch('/materiales/{id}', [
            MaterialEstudioController::class,
            'update',
        ])->whereNumber('id');

        Route::delete('/materiales/{id}', [
            MaterialEstudioController::class,
            'destroy',
        ])->whereNumber('id');

        Route::get('/informes/reuniones', [
            InformeReunionController::class,
            'index',
        ]);

        Route::get('/etapa1/consultas/1', [
            ConsultaEtapa1Controller::class,
            'casasActivas',
        ]);

        Route::get('/etapa1/consultas/2', [
            ConsultaEtapa1Controller::class,
            'lideresPorCasa',
        ]);

        Route::get('/etapa1/consultas/3', [
            ConsultaEtapa1Controller::class,
            'asistentesPorReunion',
        ]);

        Route::get('/etapa1/consultas/4', [
            ConsultaEtapa1Controller::class,
            'resumenPorCasa',
        ]);

        Route::view('/etapa1/sql', 'consultas.ejercicios');

        Route::post('/etapa1/sql/ejecutar', [
            EjerciciosSqlController::class,
            'ejecutar',
        ]);
    });

    Route::middleware('rol:Líder')->group(function () {
        Route::get('/reuniones', [
            ReunionController::class,
            'index',
        ]);

        Route::get('/reuniones/crear', [
            ReunionController::class,
            'create',
        ]);

        Route::post('/reuniones', [
            ReunionController::class,
            'store',
        ]);

        Route::get('/reuniones/{id}/editar', [
            ReunionController::class,
            'edit',
        ])->whereNumber('id');

        Route::patch('/reuniones/{id}', [
            ReunionController::class,
            'update',
        ])->whereNumber('id');

        Route::delete('/reuniones/{id}', [
            ReunionController::class,
            'destroy',
        ])->whereNumber('id');
    });
});