<?php

namespace App\Http\Controllers;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\Operation\FindOneAndUpdate;

class EventoController extends Controller
{
    private const ZONA_HORARIA = 'America/Santiago';

    public function index(Request $request)
    {
        [$usuario, $nombreRol] = $this->obtenerSesion($request);

        $casas = DB::connection('mongodb')
            ->table('casas_iglesia')
            ->get()
            ->keyBy('id');

        $idsResponsabilidad = $casas
            ->filter(fn ($casa) =>
                (bool) $casa->estado
                && (int) ($casa->id_lider_responsable ?? 0)
                    === (int) $usuario->getAuthIdentifier()
            )
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->all();

        $idsVisibles = array_values(array_unique(array_merge(
            array_map('intval', (array) ($usuario->casas_ids ?? [])),
            $nombreRol === 'Líder' ? $idsResponsabilidad : []
        )));

        $consulta = DB::connection('mongodb')->table('eventos');

        if ($nombreRol !== 'Administrador') {
            $consulta->where(function ($filtro) use ($idsVisibles) {
                $filtro->whereNull('id_casa');

                if ($idsVisibles !== []) {
                    $filtro->orWhereIn('id_casa', $idsVisibles);
                }
            });
        }

        $eventos = $consulta
            ->orderBy('fecha_hora', 'desc')
            ->orderBy('_id', 'desc')
            ->paginate(15);

        $eventos->getCollection()->transform(
            function ($evento) use (
                $usuario,
                $nombreRol,
                $casas,
                $idsResponsabilidad
            ) {
                $idCasa = $evento->id_casa ?? null;

                $puedeEditar = $nombreRol === 'Administrador'
                    || (
                        $nombreRol === 'Líder'
                        && (int) $evento->id_creador
                            === (int) $usuario->getAuthIdentifier()
                        && $idCasa !== null
                        && in_array(
                            (int) $idCasa,
                            $idsResponsabilidad,
                            true
                        )
                    );

                return (object) [
                    'id_evento' => (int) $evento->id,
                    'titulo' => $evento->titulo,
                    'descripcion' => $evento->descripcion ?? '',
                    'fecha_hora' => $this->fechaLocal(
                        $evento->fecha_hora
                    )->format('d/m/Y H:i'),
                    'nombre_casa' => $idCasa === null
                        ? 'Evento general'
                        : (
                            $casas->get((int) $idCasa)->nombre_casa
                            ?? 'Casa no disponible'
                        ),
                    'puede_editar' => $puedeEditar,
                ];
            }
        );

        $puedeCrear = in_array(
            $nombreRol,
            ['Administrador', 'Líder'],
            true
        );

        return view(
            'eventos.index',
            compact('eventos', 'puedeCrear')
        );
    }

    public function create(Request $request)
    {
        [$usuario, $nombreRol] = $this->obtenerSesion($request);

        $this->exigirGestor($nombreRol);

        $casas = $this->casasDisponibles($usuario, $nombreRol);
        $esAdministrador = $nombreRol === 'Administrador';
        $evento = null;

        return view('eventos.formulario', compact(
            'casas',
            'esAdministrador',
            'evento'
        ));
    }

    public function store(Request $request)
    {
        [$usuario, $nombreRol] = $this->obtenerSesion($request);

        $this->exigirGestor($nombreRol);

        $datos = $this->validarDatos($request);
        $idCasa = $this->validarCasa($datos, $usuario, $nombreRol);
        $fecha = $this->crearFecha($datos['fecha_hora']);

        DB::connection('mongodb')
            ->table('eventos')
            ->insert([
                '_id' => $this->siguienteIdEvento(),
                'titulo' => $datos['titulo'],
                'descripcion' => $datos['descripcion'],
                'fecha_hora' => $fecha,
                'id_creador' => (int) $usuario->getAuthIdentifier(),
                'id_casa' => $idCasa,
            ]);

        return redirect('/eventos')
            ->with('exito', 'El evento se creó correctamente.');
    }

    public function edit(Request $request, int $id)
    {
        [$usuario, $nombreRol] = $this->obtenerSesion($request);

        $evento = $this->obtenerEvento($id);
        $this->autorizarEvento($evento, $usuario, $nombreRol);

        $evento->id_evento = (int) $evento->id;
        $evento->fecha_formulario = $this->fechaLocal(
            $evento->fecha_hora
        )->format('Y-m-d\TH:i');

        $casas = $this->casasDisponibles($usuario, $nombreRol);
        $esAdministrador = $nombreRol === 'Administrador';

        if ($esAdministrador && isset($evento->id_casa)) {
            $idCasa = (int) $evento->id_casa;

            if (! $casas->contains('id_casa', $idCasa)) {
                $casaActual = DB::connection('mongodb')
                    ->table('casas_iglesia')
                    ->where('_id', $idCasa)
                    ->first();

                if ($casaActual !== null) {
                    $casas->push((object) [
                        'id_casa' => $idCasa,
                        'nombre_casa' => $casaActual->nombre_casa,
                        'estado' => (bool) $casaActual->estado,
                    ]);
                }
            }
        }

        return view('eventos.formulario', compact(
            'casas',
            'esAdministrador',
            'evento'
        ));
    }

    public function update(Request $request, int $id)
    {
        [$usuario, $nombreRol] = $this->obtenerSesion($request);

        $evento = $this->obtenerEvento($id);
        $this->autorizarEvento($evento, $usuario, $nombreRol);

        $datos = $this->validarDatos($request);
        $idCasa = $this->validarCasa(
            $datos,
            $usuario,
            $nombreRol,
            $evento
        );

        DB::connection('mongodb')
            ->table('eventos')
            ->where('_id', $id)
            ->update([
                'titulo' => $datos['titulo'],
                'descripcion' => $datos['descripcion'],
                'fecha_hora' => $this->crearFecha($datos['fecha_hora']),
                'id_casa' => $idCasa,
            ]);

        return redirect('/eventos')
            ->with('exito', 'El evento se actualizó correctamente.');
    }

    public function destroy(Request $request, int $id)
    {
        [$usuario, $nombreRol] = $this->obtenerSesion($request);

        $evento = $this->obtenerEvento($id);
        $this->autorizarEvento($evento, $usuario, $nombreRol);

        DB::connection('mongodb')
            ->table('eventos')
            ->where('_id', $id)
            ->delete();

        return redirect('/eventos')
            ->with('exito', 'El evento se eliminó correctamente.');
    }

    private function obtenerSesion(Request $request): array
    {
        $usuario = $request->user();

        abort_if($usuario === null, 401, 'Debes iniciar sesión.');
        abort_unless($usuario->estado, 403, 'Tu cuenta está inactiva.');

        $rol = DB::connection('mongodb')
            ->table('roles')
            ->where('_id', (int) $usuario->id_rol)
            ->first();

        $nombreRol = $rol->nombre_rol ?? '';

        abort_unless(
            in_array(
                $nombreRol,
                ['Administrador', 'Líder', 'Participante'],
                true
            ),
            403,
            'Tu cuenta no tiene un rol autorizado.'
        );

        return [$usuario, $nombreRol];
    }

    private function exigirGestor(string $nombreRol): void
    {
        abort_unless(
            in_array($nombreRol, ['Administrador', 'Líder'], true),
            403,
            'No tienes permiso para gestionar eventos.'
        );
    }

    private function obtenerEvento(int $id): object
    {
        $evento = DB::connection('mongodb')
            ->table('eventos')
            ->where('_id', $id)
            ->first();

        abort_if($evento === null, 404, 'El evento no existe.');

        return $evento;
    }

    private function autorizarEvento(
        object $evento,
        object $usuario,
        string $nombreRol
    ): void {
        $this->exigirGestor($nombreRol);

        if ($nombreRol === 'Administrador') {
            return;
        }

        abort_unless(
            (int) $evento->id_creador
                === (int) $usuario->getAuthIdentifier()
            && isset($evento->id_casa),
            403,
            'Solo puedes gestionar los eventos que creaste para tus Casas.'
        );

        $esResponsable = DB::connection('mongodb')
            ->table('casas_iglesia')
            ->where('_id', (int) $evento->id_casa)
            ->where('estado', true)
            ->where(
                'id_lider_responsable',
                (int) $usuario->getAuthIdentifier()
            )
            ->exists();

        abort_unless(
            $esResponsable,
            403,
            'La Casa debe estar activa y bajo tu responsabilidad.'
        );
    }

    private function casasDisponibles(
        object $usuario,
        string $nombreRol
    ) {
        $consulta = DB::connection('mongodb')
            ->table('casas_iglesia')
            ->where('estado', true);

        if ($nombreRol === 'Líder') {
            $consulta->where(
                'id_lider_responsable',
                (int) $usuario->getAuthIdentifier()
            );
        }

        return $consulta
            ->orderBy('nombre_casa')
            ->get()
            ->map(fn ($casa) => (object) [
                'id_casa' => (int) $casa->id,
                'nombre_casa' => $casa->nombre_casa,
                'estado' => (bool) $casa->estado,
            ]);
    }

    private function validarDatos(Request $request): array
    {
        return $request->validate([
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['required', 'string', 'max:3000'],
            'fecha_hora' => ['required', 'date_format:Y-m-d\TH:i'],
            'id_casa' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    private function validarCasa(
        array $datos,
        object $usuario,
        string $nombreRol,
        ?object $evento = null
    ): ?int {
        $valor = $datos['id_casa'] ?? null;

        if ($valor === null) {
            if ($nombreRol !== 'Administrador') {
                throw ValidationException::withMessages([
                    'id_casa' => 'Selecciona una Casa bajo tu responsabilidad.',
                ]);
            }

            return null;
        }

        $idCasa = (int) $valor;

        $casa = DB::connection('mongodb')
            ->table('casas_iglesia')
            ->where('_id', $idCasa)
            ->first();

        if ($casa === null) {
            throw ValidationException::withMessages([
                'id_casa' => 'La Casa seleccionada no existe.',
            ]);
        }

        $conservaCasaInactiva = $nombreRol === 'Administrador'
            && $evento !== null
            && isset($evento->id_casa)
            && (int) $evento->id_casa === $idCasa;

        if (! $casa->estado && ! $conservaCasaInactiva) {
            throw ValidationException::withMessages([
                'id_casa' => 'Selecciona una Casa activa.',
            ]);
        }

        if ($nombreRol === 'Líder') {
            abort_unless(
                (int) ($casa->id_lider_responsable ?? 0)
                    === (int) $usuario->getAuthIdentifier(),
                403,
                'No puedes crear o trasladar eventos a una Casa ajena.'
            );
        }

        return $idCasa;
    }

    private function crearFecha(string $valor): UTCDateTime
    {
        $fecha = DateTimeImmutable::createFromFormat(
            '!Y-m-d\TH:i',
            $valor,
            new DateTimeZone(self::ZONA_HORARIA)
        );

        if ($fecha === false || $fecha->format('Y-m-d\TH:i') !== $valor) {
            throw ValidationException::withMessages([
                'fecha_hora' => 'La fecha y hora no son válidas para la zona horaria de Santiago.',
            ]);
        }

        return new UTCDateTime($fecha);
    }

    private function fechaLocal(
        DateTimeInterface|UTCDateTime $fecha
    ): DateTimeImmutable {
        $valor = $fecha instanceof UTCDateTime
            ? $fecha->toDateTime()
            : $fecha;

        return DateTimeImmutable::createFromInterface($valor)
            ->setTimezone(new DateTimeZone(self::ZONA_HORARIA));
    }

    private function siguienteIdEvento(): int
    {
        $maximoActual = (int) (
            DB::connection('mongodb')
                ->table('eventos')
                ->max('_id') ?? 0
        );

        return DB::connection('mongodb')
            ->table('contadores')
            ->raw(function (Collection $coleccion) use ($maximoActual) {
                $coleccion->updateOne(
                    ['_id' => 'eventos'],
                    ['$max' => ['secuencia' => $maximoActual]],
                    ['upsert' => true]
                );

                $contador = $coleccion->findOneAndUpdate(
                    ['_id' => 'eventos'],
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