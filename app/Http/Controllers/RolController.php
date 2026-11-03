<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RolController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = DB::connection('mongodb')
            ->table('roles')
            ->orderBy('_id')
            ->get()
            ->map(fn ($rol) => [
                'id_rol' => $rol->id,
                'nombre_rol' => $rol->nombre_rol,
            ]);

        return response()->json($roles);
    }
}