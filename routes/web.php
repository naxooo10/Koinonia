<?php

use App\Http\Controllers\CasaIglesiaController;
use App\Http\Controllers\ConsultaEtapa1Controller;
use App\Http\Controllers\EjerciciosSqlController;
use App\Http\Controllers\InformeReunionController;
use App\Http\Controllers\ReunionController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;
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
    Route::get('/roles', [RolController::class, 'index']);

    Route::get('/casas', [CasaIglesiaController::class, 'index']);
    Route::get('/casas/crear', [CasaIglesiaController::class, 'create']);
    Route::post('/casas', [CasaIglesiaController::class, 'store']);
    Route::get('/casas/{id}/editar', [CasaIglesiaController::class, 'edit']);
    Route::patch('/casas/{id}', [CasaIglesiaController::class, 'update']);
    Route::patch('/casas/{id}/desactivar', [CasaIglesiaController::class, 'deactivate']);

    Route::get('/usuarios', [UsuarioController::class, 'index']);
    Route::get('/usuarios/crear', [UsuarioController::class, 'create']);
    Route::post('/usuarios', [UsuarioController::class, 'store']);
    Route::get('/usuarios/{id}/editar', [UsuarioController::class, 'edit']);
    Route::patch('/usuarios/{id}', [UsuarioController::class, 'update']);
    Route::patch('/usuarios/{id}/desactivar', [UsuarioController::class, 'deactivate']);

    Route::view('/reuniones/crear', 'reuniones.crear');
    Route::post('/reuniones', [ReunionController::class, 'store']);

    Route::get('/informes/reuniones', [InformeReunionController::class, 'index']);

    Route::get('/etapa1/consultas/1', [ConsultaEtapa1Controller::class, 'casasActivas']);
    Route::get('/etapa1/consultas/2', [ConsultaEtapa1Controller::class, 'lideresPorCasa']);
    Route::get('/etapa1/consultas/3', [ConsultaEtapa1Controller::class, 'asistentesPorReunion']);

    Route::view('/etapa1/sql', 'consultas.ejercicios');
    Route::post('/etapa1/sql/ejecutar', [EjerciciosSqlController::class, 'ejecutar']);
});