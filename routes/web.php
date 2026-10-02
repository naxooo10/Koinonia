<?php

use App\Http\Controllers\CasaIglesiaController;
use App\Http\Controllers\ConsultaEtapa1Controller;
use App\Http\Controllers\EjerciciosSqlController;
use App\Http\Controllers\InformeReunionController;
use App\Http\Controllers\ReunionController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

Route::get('/salud', function () {
    DB::select('SELECT 1');

    return response()->json([
        'aplicacion' => 'Koinonía',
        'base_de_datos' => 'conectada',
    ]);
});

Route::middleware('acceso.demo')->group(function () {
    // Panel de demostración.
    Route::view('/panel', 'panel.inicio');
    Route::view('/panel/casas', 'panel.casas');
    Route::view('/panel/usuarios', 'panel.usuarios');
    Route::view('/panel/reuniones', 'panel.reuniones');
    Route::view('/panel/consultas', 'panel.consultas');

    // Roles.
    Route::get('/roles', [RolController::class, 'index']);

    // Casas Iglesia.
    Route::get('/casas', [CasaIglesiaController::class, 'index']);
    Route::get('/casas/crear', [CasaIglesiaController::class, 'create']);
    Route::post('/casas', [CasaIglesiaController::class, 'store']);
    Route::get('/casas/{id}/editar', [CasaIglesiaController::class, 'edit']);
    Route::patch('/casas/{id}', [CasaIglesiaController::class, 'update']);
    Route::patch('/casas/{id}/desactivar', [CasaIglesiaController::class, 'deactivate']);
    Route::patch('/casas/{id}/activar', [CasaIglesiaController::class, 'activate']);

    // Usuarios.
    Route::get('/usuarios', [UsuarioController::class, 'index']);
    Route::get('/usuarios/crear', [UsuarioController::class, 'create']);
    Route::post('/usuarios', [UsuarioController::class, 'store']);
    Route::get('/usuarios/{id}/editar', [UsuarioController::class, 'edit']);
    Route::patch('/usuarios/{id}', [UsuarioController::class, 'update']);
    Route::patch('/usuarios/{id}/desactivar', [UsuarioController::class, 'deactivate']);

    // Reuniones e informes.
    Route::get('/reuniones/crear', [ReunionController::class, 'create']);
    Route::post('/reuniones', [ReunionController::class, 'store']);
    Route::get('/informes/reuniones', [InformeReunionController::class, 'index']);

    // Consultas SELECT de Etapa 1.
    Route::get('/etapa1/consultas/1', [ConsultaEtapa1Controller::class, 'casasActivas']);
    Route::get('/etapa1/consultas/2', [ConsultaEtapa1Controller::class, 'lideresPorCasa']);
    Route::get('/etapa1/consultas/3', [ConsultaEtapa1Controller::class, 'asistentesPorReunion']);

    // Ejercicios SQL de Etapa 1.
    Route::view('/etapa1/sql', 'consultas.ejercicios');
    Route::post('/etapa1/sql/ejecutar', [EjerciciosSqlController::class, 'ejecutar']);
});