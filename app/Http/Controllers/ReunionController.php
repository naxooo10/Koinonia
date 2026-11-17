<?php

namespace App\Http\Controllers;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\Operation\FindOneAndUpdate;

class ReunionController extends Controller
{
    public function index(Request $request)
    {
        $lider = $this->obtenerLiderSesion($request);
        $casas = $this->obtenerCasasDelLider($lider->id_usuario);
        $casasPorId = $casas->keyBy('id_casa');

        $reuniones = DB::connection('mongodb')
            ->table('reuniones')
            ->whereIn('id_casa', $casas->pluck('id_casa')->all())
            ->orderBy('fecha', 'desc')
            ->orderBy('_id', 'desc')
            ->paginate(15);

        $reuniones->getCollection()->transform(
            function ($reunion) use ($casasPorId) {
                $asistencia = collect($reunion->asistencia ?? []);

                return (object) [
                    'id_reunion' => (int) $reunion->id,
                    'fecha' => $this->formatearFecha($reunion->fecha),
                    'tema' => $reunion->tema,
                    'nombre_casa' => $casasPorId
                        ->get((int) $reunion->id_casa)
                        ->nombre_casa ?? 'Casa no disponible',
                    'presentes' => $asistencia
                        ->where('estado', 'presente')
                        ->count(),
                    'ausentes' => $asistencia
                        ->where('estado', 'ausente')
                        ->count(),
                    'pendientes' => $asistencia
                        ->where('estado', 'pendiente')
                        ->count(),
                ];
            }
        );

        return view('reuniones.index', compact('reuniones'));
    }

    public function create(Request $request)
    {
        $lider = $this->obtenerLiderSesion($request);

        $datos = $request->validate([
            'id_casa' => ['nullable', 'integer', 'min:1'],
        ]);

        $casas = $this->obtenerCasasDelLider($lider->id_usuario);

        $casa = null;
        $participantes = collect();

        if (! empty($datos['id_casa'])) {
            $idCasa = (int) $datos['id_casa'];

            $casa = $casas->firstWhere('id_casa', $idCasa);

            abort_if(
                $casa === null,
                403,
                'No puedes gestionar esta Casa. Debe estar activa y bajo tu responsabilidad.'
            );

            $participantes = $this->obtenerPersonasEsperadas($idCasa);
        }

        return view('reuniones.crear', compact(
            'casas',
            'casa',
            'lider',
            'participantes'
        ));
    }

    public function store(Request $request)
    {
        $lider = $this->obtenerLiderSesion($request);

        $request->merge([
            'asistencia' => $request->input('asistencia', []),
        ]);

        $datos = $request->validate(array_merge(
            [
                'id_casa' => ['required', 'integer', 'min:1'],
            ],
            $this->reglasReunion()
        ));

        $idCasa = (int) $datos['id_casa'];

        $this->obtenerCasaAutorizada($idCasa, $lider->id_usuario);

        $personasEsperadas = $this->obtenerPersonasEsperadas($idCasa);

        $idsEsperados = $personasEsperadas
            ->pluck('id_usuario')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($datos['asistencia'] as $registro) {
            if (
                ! in_array(
                    (int) $registro['usuario_id'],
                    $idsEsperados,
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'asistencia' => 'La asistencia contiene una persona que no es integrante activo de esta Casa. Recarga la lista.',
                ]);
            }
        }

        $registrosEnviados = collect($datos['asistencia'])
            ->keyBy(fn ($registro) => (int) $registro['usuario_id']);

        $asistencia = $personasEsperadas
            ->map(function ($persona) use ($registrosEnviados) {
                $idUsuario = (int) $persona->id_usuario;
                $registro = $registrosEnviados->get($idUsuario);

                return $this->normalizarAsistencia([
                    'usuario_id' => $idUsuario,
                    'estado' => $registro['estado'] ?? 'pendiente',
                    'justificacion' => $registro['justificacion'] ?? null,
                ]);
            })
            ->values()
            ->all();

        $idReunion = $this->siguienteIdReunion();

        DB::connection('mongodb')
            ->table('reuniones')
            ->insert([
                '_id' => $idReunion,
                'fecha' => $this->crearFecha($datos['fecha']),
                'tema' => $datos['tema'],
                'descripcion' => $this->normalizarDescripcion(
                    $datos['descripcion'] ?? null
                ),
                'id_casa' => $idCasa,
                'asistencia' => $asistencia,
            ]);

        return response()->json(
            $this->resumenAsistencia($idReunion, $asistencia),
            201
        );
    }

    public function edit(Request $request, int $id)
    {
        $lider = $this->obtenerLiderSesion($request);
        $reunion = $this->obtenerReunion($id);

        $casa = $this->obtenerCasaAutorizada(
            (int) $reunion->id_casa,
            $lider->id_usuario
        );

        $reunion->id_reunion = (int) $reunion->id;
        $reunion->fecha_formulario = $this->formatearFecha(
            $reunion->fecha
        );

        $registros = collect($reunion->asistencia ?? [])
            ->map(fn ($registro) => (array) $registro);

        $idsUsuarios = $registros
            ->pluck('usuario_id')
            ->map(fn ($idUsuario) => (int) $idUsuario)
            ->all();

        $usuarios = collect();

        if ($idsUsuarios !== []) {
            $usuarios = DB::connection('mongodb')
                ->table('usuarios')
                ->whereIn('_id', $idsUsuarios)
                ->get()
                ->keyBy('id');
        }

        $asistencia = $registros
            ->map(function ($registro) use ($usuarios) {
                $idUsuario = (int) $registro['usuario_id'];
                $usuario = $usuarios->get($idUsuario);

                return (object) [
                    'usuario_id' => $idUsuario,
                    'nombre' => $usuario->nombre
                        ?? "Usuario #{$idUsuario} no disponible",
                    'estado' => $registro['estado'] ?? 'pendiente',
                    'justificacion' => $registro['justificacion'] ?? null,
                ];
            })
            ->values();

        return view('reuniones.editar', compact(
            'reunion',
            'casa',
            'asistencia'
        ));
    }

    public function update(Request $request, int $id)
    {
        $lider = $this->obtenerLiderSesion($request);
        $reunion = $this->obtenerReunion($id);

        $this->obtenerCasaAutorizada(
            (int) $reunion->id_casa,
            $lider->id_usuario
        );

        if ($request->input('asistencia') === null) {
            $request->merge(['asistencia' => []]);
        }

        $datos = $request->validate($this->reglasReunion());

        $idsOriginales = collect($reunion->asistencia ?? [])
            ->map(fn ($registro) => (array) $registro)
            ->pluck('usuario_id')
            ->map(fn ($idUsuario) => (int) $idUsuario)
            ->sort()
            ->values()
            ->all();

        $idsEnviados = collect($datos['asistencia'])
            ->pluck('usuario_id')
            ->map(fn ($idUsuario) => (int) $idUsuario)
            ->sort()
            ->values()
            ->all();

        if ($idsOriginales !== $idsEnviados) {
            throw ValidationException::withMessages([
                'asistencia' => 'Debes conservar los integrantes registrados originalmente en esta reunión. Recarga la página.',
            ]);
        }

        $asistencia = array_map(
            fn ($registro) => $this->normalizarAsistencia($registro),
            $datos['asistencia']
        );

        DB::connection('mongodb')
            ->table('reuniones')
            ->where('_id', $id)
            ->where('id_casa', (int) $reunion->id_casa)
            ->update([
                'fecha' => $this->crearFecha($datos['fecha']),
                'tema' => $datos['tema'],
                'descripcion' => $this->normalizarDescripcion(
                    $datos['descripcion'] ?? null
                ),
                'asistencia' => $asistencia,
            ]);

        if ($request->expectsJson()) {
            return response()->json(
                $this->resumenAsistencia($id, $asistencia)
            );
        }

        return redirect('/reuniones')
            ->with('exito', 'La reunión se actualizó correctamente.');
    }

    public function destroy(Request $request, int $id)
    {
        $lider = $this->obtenerLiderSesion($request);
        $reunion = $this->obtenerReunion($id);

        $this->obtenerCasaAutorizada(
            (int) $reunion->id_casa,
            $lider->id_usuario
        );

        $eliminadas = DB::connection('mongodb')
            ->table('reuniones')
            ->where('_id', $id)
            ->where('id_casa', (int) $reunion->id_casa)
            ->delete();

        abort_if(
            $eliminadas === 0,
            404,
            'La reunión ya no existe.'
        );

        if ($request->expectsJson()) {
            return response()->json([
                'mensaje' => 'La reunión y su asistencia se eliminaron correctamente.',
                'id_reunion' => $id,
            ]);
        }

        return redirect('/reuniones')
            ->with(
                'exito',
                'La reunión y su asistencia se eliminaron correctamente.'
            );
    }

    private function reglasReunion(): array
    {
        return [
            'fecha' => ['required', 'date_format:Y-m-d'],
            'tema' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:3000'],
            'asistencia' => ['present', 'array', 'list'],
            'asistencia.*' => [
                'required',
                'array:usuario_id,estado,justificacion',
            ],
            'asistencia.*.usuario_id' => [
                'required',
                'integer',
                'min:1',
                'distinct',
            ],
            'asistencia.*.estado' => [
                'sometimes',
                'required',
                Rule::in(['presente', 'ausente', 'pendiente']),
            ],
            'asistencia.*.justificacion' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * El campo justificacion guarda el motivo de ausencia: solo se
     * conserva cuando la persona queda ausente, para que un motivo
     * escrito antes de cambiar el estado no quede asociado a un
     * presente o a un pendiente.
     */
    private function normalizarAsistencia(array $registro): array
    {
        $estado = $registro['estado'] ?? 'pendiente';

        $justificacion = trim(
            (string) ($registro['justificacion'] ?? '')
        );

        return [
            'usuario_id' => (int) $registro['usuario_id'],
            'estado' => $estado,
            'justificacion' => $estado !== 'ausente' || $justificacion === ''
                ? null
                : $justificacion,
        ];
    }

    private function normalizarDescripcion(?string $descripcion): ?string
    {
        $descripcion = trim((string) $descripcion);

        return $descripcion === '' ? null : $descripcion;
    }

    private function resumenAsistencia(
        int $idReunion,
        array $asistencia
    ): array {
        $registros = collect($asistencia);

        return [
            'id_reunion' => $idReunion,
            'asistentes_registrados' => $registros
                ->where('estado', 'presente')
                ->count(),
            'ausentes' => $registros
                ->where('estado', 'ausente')
                ->count(),
            'pendientes' => $registros
                ->where('estado', 'pendiente')
                ->count(),
        ];
    }

    private function obtenerReunion(int $id): object
    {
        $reunion = DB::connection('mongodb')
            ->table('reuniones')
            ->where('_id', $id)
            ->first();

        abort_if($reunion === null, 404, 'La reunión no existe.');

        return $reunion;
    }

    private function obtenerCasaAutorizada(
        int $idCasa,
        int $idLider
    ): object {
        $casa = DB::connection('mongodb')
            ->table('casas_iglesia')
            ->where('_id', $idCasa)
            ->where('estado', true)
            ->where('id_lider_responsable', $idLider)
            ->first();

        abort_if(
            $casa === null,
            403,
            'No puedes gestionar esta Casa. Debe estar activa y bajo tu responsabilidad.'
        );

        return $casa;
    }

    private function obtenerCasasDelLider(int $idLider)
    {
        return DB::connection('mongodb')
            ->table('casas_iglesia')
            ->where('estado', true)
            ->where('id_lider_responsable', $idLider)
            ->orderBy('nombre_casa')
            ->get()
            ->map(fn ($registro) => (object) [
                'id_casa' => (int) $registro->id,
                'nombre_casa' => $registro->nombre_casa,
                'direccion' => $registro->direccion,
                'estado' => (bool) $registro->estado,
                'id_lider_responsable' =>
                    $registro->id_lider_responsable ?? null,
            ]);
    }

    private function crearFecha(string $fecha): UTCDateTime
    {
        return new UTCDateTime(
            new DateTimeImmutable(
                $fecha . ' 00:00:00',
                new DateTimeZone('UTC')
            )
        );
    }

    private function formatearFecha(
        DateTimeInterface|UTCDateTime $fecha
    ): string {
        $fechaUtc = $fecha instanceof UTCDateTime
            ? $fecha->toDateTime()
            : $fecha;

        return DateTimeImmutable::createFromInterface($fechaUtc)
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d');
    }

    private function obtenerLiderSesion(Request $request): object
    {
        $usuario = $request->user();

        abort_if(
            $usuario === null,
            401,
            'Debes iniciar sesión.'
        );

        abort_unless(
            $usuario->estado,
            403,
            'Tu cuenta está inactiva.'
        );

        $lider = $this->obtenerLider(
            (int) $usuario->getAuthIdentifier()
        );

        abort_if(
            $lider === null,
            403,
            'Debes tener el rol Líder para gestionar reuniones.'
        );

        return $lider;
    }

    private function obtenerLider(?int $idLider): ?object
    {
        if ($idLider === null) {
            return null;
        }

        $rol = DB::connection('mongodb')
            ->table('roles')
            ->where('nombre_rol', 'Líder')
            ->first();

        if ($rol === null) {
            return null;
        }

        $usuario = DB::connection('mongodb')
            ->table('usuarios')
            ->where('_id', $idLider)
            ->where('estado', true)
            ->where('id_rol', $rol->id)
            ->first();

        if ($usuario === null) {
            return null;
        }

        return (object) [
            'id_usuario' => (int) $usuario->id,
            'nombre' => $usuario->nombre,
        ];
    }

    private function obtenerPersonasEsperadas(int $idCasa)
    {
        return DB::connection('mongodb')
            ->table('usuarios')
            ->where('estado', true)
            ->where('casas_ids', $idCasa)
            ->orderBy('nombre')
            ->get()
            ->map(fn ($usuario) => (object) [
                'id_usuario' => (int) $usuario->id,
                'nombre' => $usuario->nombre,
            ]);
    }

    private function siguienteIdReunion(): int
    {
        $maximoActual = (int) (
            DB::connection('mongodb')
                ->table('reuniones')
                ->max('_id') ?? 0
        );

        return DB::connection('mongodb')
            ->table('contadores')
            ->raw(function (Collection $coleccion) use ($maximoActual) {
                $coleccion->updateOne(
                    ['_id' => 'reuniones'],
                    ['$max' => ['secuencia' => $maximoActual]],
                    ['upsert' => true]
                );

                $contador = $coleccion->findOneAndUpdate(
                    ['_id' => 'reuniones'],
                    ['$inc' => ['secuencia' => 1]],
                    [
                        'returnDocument' =>
                            FindOneAndUpdate::RETURN_DOCUMENT_AFTER,
                    ]
                );

                return (int) $contador['secuencia'];
            });
    }
}