<?php
use App\Http\Controllers\EjerciciosSqlController;
use App\Http\Controllers\ConsultaEtapa1Controller;
use App\Http\Controllers\InformeReunionController;
use App\Http\Controllers\ReunionController;
use App\Http\Controllers\UsuarioController;
use App\Http\controllers\CasaIglesiaController;
use App\Http\Controllers\RolController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/salud', function () {
    DB::select('SELECT 1');

    return response()->json([
        'aplicacion' => 'Koinonía',
        'base_de_datos' => 'conectada',
    ]);
});
Route::get('/roles', [RolController::class, 'index']);
Route::get('/casas', function (CasaIglesiaController $controller) {
    abort_unless(app()->environment('local'), 404);

    return $controller->index();
});
Route::get('/casas/crear', function (CasaIglesiaController $controller) {
    abort_unless(app()->environment('local'), 404);

    return $controller->create();
});

Route::post('/casas', function (CasaIglesiaController $controller, \Illuminate\Http\Request $request) {
    abort_unless(app()->environment('local'), 404);

    return $controller->store($request);
});
Route::patch('/casas/{id}/desactivar', function (CasaIglesiaController $controller, int $id) {
    abort_unless(app()->environment('local'), 404);

    return $controller->deactivate($id);
});
Route::get('/casas/{id}/editar', function (CasaIglesiaController $controller, int $id) {
    abort_unless(app()->environment('local'), 404);

    return $controller->edit($id);
});

Route::patch('/casas/{id}', function (
    CasaIglesiaController $controller,
    \Illuminate\Http\Request $request,
    int $id
) {
    abort_unless(app()->environment('local'), 404);

    return $controller->update($request, $id);
});
Route::get('/usuarios', function (UsuarioController $controller) {
    abort_unless(app()->environment('local'), 404);

    return $controller->index();
});
Route::get('/usuarios/crear', function (UsuarioController $controller) {
    abort_unless(app()->environment('local'), 404);

    return $controller->create();
});

Route::post('/usuarios', function (
    UsuarioController $controller,
    \Illuminate\Http\Request $request
) {
    abort_unless(app()->environment('local'), 404);

    return $controller->store($request);
});
Route::get('/usuarios/{id}/editar', function (UsuarioController $controller, int $id) {
    abort_unless(app()->environment('local'), 404);

    return $controller->edit($id);
});

Route::patch('/usuarios/{id}', function (
    UsuarioController $controller,
    \Illuminate\Http\Request $request,
    int $id
) {
    abort_unless(app()->environment('local'), 404);

    return $controller->update($request, $id);
});
Route::patch('/usuarios/{id}/desactivar', function (UsuarioController $controller, int $id) {
    abort_unless(app()->environment('local'), 404);

    return $controller->deactivate($id);
});
Route::get('/reuniones/crear', function () {
    abort_unless(app()->environment('local'), 404);

    return view('reuniones.crear');
});

Route::post('/reuniones', function (
    ReunionController $controller,
    \Illuminate\Http\Request $request
) {
    abort_unless(app()->environment('local'), 404);

    return $controller->store($request);
});
Route::get('/informes/reuniones', function (
    InformeReunionController $controller,
    \Illuminate\Http\Request $request
) {
    abort_unless(app()->environment('local'), 404);

    return $controller->index($request);
});
if (app()->environment('local')) {
    Route::get(
        '/etapa1/consultas/1',
        [ConsultaEtapa1Controller::class, 'casasActivas']
    );

    Route::get(
        '/etapa1/consultas/2',
        [ConsultaEtapa1Controller::class, 'lideresPorCasa']
    );

    Route::get(
        '/etapa1/consultas/3',
        [ConsultaEtapa1Controller::class, 'asistentesPorReunion']
    );
}if (app()->environment('local')) {
    Route::view('/etapa1/sql', 'consultas.ejercicios');

    Route::post(
        '/etapa1/sql/ejecutar',
        [EjerciciosSqlController::class, 'ejecutar']
    );
}