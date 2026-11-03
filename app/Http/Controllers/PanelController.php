<?php

namespace App\Http\Controllers;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;

class PanelController extends Controller
{
    public function index(Request $request)
    {
        $usuario = $request->user();
        abort_if($usuario === null, 401);
        abort_unless((bool) $usuario->estado, 403);

        $conexion = DB::connection('mongodb');
        $rol = $conexion->table('roles')
            ->where('_id', (int) $usuario->id_rol)->first();
        $nombreRol = $rol->nombre_rol ?? '';
        abort_unless(in_array($nombreRol, ['Administrador', 'Líder', 'Participante'], true), 403);

        $esAdministrador = $nombreRol === 'Administrador';
        $esLider = $nombreRol === 'Líder';
        $idUsuario = (int) $usuario->getAuthIdentifier();
        $idsPertenencia = array_values(array_unique(array_map(
            'intval', (array) ($usuario->casas_ids ?? [])
        )));

        $consultaCasas = $conexion->table('casas_iglesia');
        if ($esLider) {
            $consultaCasas->where('estado', true)
                ->where('id_lider_responsable', $idUsuario);
        } elseif (! $esAdministrador) {
            $consultaCasas->whereIn('_id', $idsPertenencia);
        }

        $casas = $consultaCasas->orderBy('nombre_casa')->get();
        $casasPorId = $casas->keyBy('id');
        $idsActivas = $casas->filter(fn ($casa) => (bool) $casa->estado)
            ->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $idsVisiblesEventos = array_values(array_unique(array_merge(
            $idsPertenencia, $esLider ? $idsActivas : []
        )));

        $hoy = now('America/Santiago')->toDateString();
        $fechaDesde = new UTCDateTime(new DateTimeImmutable(
            $hoy . ' 00:00:00', new DateTimeZone('UTC')
        ));

        $reuniones = collect();
        if ($idsActivas !== []) {
            $reuniones = $conexion->table('reuniones')
                ->whereIn('id_casa', $idsActivas)
                ->where('fecha', '>=', $fechaDesde)
                ->select('_id', 'id_casa', 'fecha', 'tema')
                ->orderBy('fecha')->orderBy('_id')->get();
        }

        $proximasPorCasa = $reuniones->groupBy('id_casa')
            ->map(fn ($grupo) => $grupo->first());
        $idsLideres = $casas->pluck('id_lider_responsable')
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)->unique()->values()->all();
        $lideres = $idsLideres === [] ? collect() : $conexion->table('usuarios')
            ->whereIn('_id', $idsLideres)->select('_id', 'nombre', 'estado')
            ->get()->keyBy('id');

        $casasPanel = $casas->map(function ($casa) use ($lideres, $proximasPorCasa) {
            $idLider = $casa->id_lider_responsable ?? null;
            $lider = $idLider === null ? null : $lideres->get((int) $idLider);
            $proxima = $proximasPorCasa->get((int) $casa->id);

            return [
                'id' => (int) $casa->id,
                'nombre' => $casa->nombre_casa,
                'direccion' => $casa->direccion ?? '',
                'activa' => (bool) $casa->estado,
                'lider' => $idLider === null ? 'Sin líder asignado'
                    : ($lider->nombre ?? 'Líder no disponible'),
                'lider_inactivo' => $lider !== null && ! (bool) $lider->estado,
                'proxima_fecha' => $proxima === null ? null
                    : $this->fecha($proxima->fecha, 'UTC')->format('d/m/Y'),
                'proximo_tema' => $proxima->tema ?? null,
            ];
        })->values()->all();

        $consultaEventos = $conexion->table('eventos')
            ->where('fecha_hora', '>=', new UTCDateTime(new DateTimeImmutable('now')));
        if (! $esAdministrador) {
            $consultaEventos->where(function ($consulta) use ($idsVisiblesEventos) {
                $consulta->whereNull('id_casa');
                if ($idsVisiblesEventos !== []) {
                    $consulta->orWhereIn('id_casa', $idsVisiblesEventos);
                }
            });
        }
        $eventos = $consultaEventos->select('_id', 'titulo', 'fecha_hora', 'id_casa')
            ->orderBy('fecha_hora')->orderBy('_id')->limit(4)->get();

        $idsCasasEventos = $eventos->pluck('id_casa')->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)->unique()->values()->all();
        $casasEventos = $idsCasasEventos === [] ? collect() : $conexion->table('casas_iglesia')
            ->whereIn('_id', $idsCasasEventos)->select('_id', 'nombre_casa')->get()->keyBy('id');

        $agenda = $reuniones->take(4)->map(function ($reunion) use ($casasPorId, $esLider, $esAdministrador) {
            $fecha = $this->fecha($reunion->fecha, 'UTC');
            $casa = $casasPorId->get((int) $reunion->id_casa);

            return [
                'titulo' => $reunion->tema,
                'dia' => $fecha->format('d'),
                'mes' => $this->mes($fecha),
                'fecha' => $fecha->format('d/m/Y'),
                'detalle' => 'Reunión de Casa Iglesia',
                'lugar' => $casa->nombre_casa ?? 'Casa no disponible',
                'tipo' => 'reunion',
                'orden' => $fecha->format('Y-m-d') . ' 00:00:00',
                'href' => $esLider ? '/reuniones/' . (int) $reunion->id . '/editar'
                    : ($esAdministrador ? '/panel/reuniones' : '#mis-casas'),
            ];
        })->merge($eventos->map(function ($evento) use ($casasEventos) {
            $fecha = $this->fecha($evento->fecha_hora, 'America/Santiago');
            $idCasa = $evento->id_casa ?? null;
            $casa = $idCasa === null ? null : $casasEventos->get((int) $idCasa);

            return [
                'titulo' => $evento->titulo,
                'dia' => $fecha->format('d'),
                'mes' => $this->mes($fecha),
                'fecha' => $fecha->format('d/m/Y'),
                'detalle' => 'Evento · ' . $fecha->format('H:i') . ' h · Santiago',
                'lugar' => $idCasa === null ? 'Toda la comunidad'
                    : ($casa->nombre_casa ?? 'Casa no disponible'),
                'tipo' => 'evento',
                'orden' => $fecha->format('Y-m-d H:i:s'),
                'href' => '/eventos',
            ];
        }))->sortBy('orden')->take(4)->values()->all();

        $proximaReunion = null;
        if ($reuniones->isNotEmpty()) {
            $reunion = $reuniones->first();
            $fecha = $this->fecha($reunion->fecha, 'UTC');
            $casa = $casasPorId->get((int) $reunion->id_casa);
            $proximaReunion = [
                'dia' => $fecha->format('d'),
                'mes' => $this->mes($fecha),
                'fecha' => $fecha->format('d/m/Y'),
                'tema' => $reunion->tema,
                'casa' => $casa->nombre_casa ?? 'Casa no disponible',
                'direccion' => $casa->direccion ?? '',
            ];
        }

        $navegacion = $this->navegacion($nombreRol);
        $modulos = $esAdministrador ? [
            ['icono' => 'home', 'color' => 'azul', 'titulo' => 'Casas Iglesias', 'texto' => 'Gestiona las Casas y sus líderes responsables.', 'href' => '/panel/casas'],
            ['icono' => 'users', 'color' => 'violeta', 'titulo' => 'Usuarios', 'texto' => 'Administra personas, roles y pertenencias.', 'href' => '/panel/usuarios'],
            ['icono' => 'chart', 'color' => 'verde', 'titulo' => 'Informes', 'texto' => 'Consulta reuniones, asistencia y justificaciones.', 'href' => '/panel/reuniones'],
            ['icono' => 'calendar', 'color' => 'ambar', 'titulo' => 'Eventos', 'texto' => 'Organiza encuentros para tu comunidad.', 'href' => '/eventos'],
            ['icono' => 'folder', 'color' => 'violeta', 'titulo' => 'Materiales', 'texto' => 'Comparte y organiza recursos de estudio.', 'href' => '/materiales'],
            ['icono' => 'database', 'color' => 'cian', 'titulo' => 'Consultas', 'texto' => 'Explora las cuatro consultas de MongoDB.', 'href' => '/panel/consultas'],
        ] : [];

        $acciones = $esAdministrador ? [
            ['icono' => 'user-plus', 'color' => 'azul', 'titulo' => 'Nuevo usuario', 'texto' => 'Registrar una persona', 'href' => '/usuarios/crear'],
            ['icono' => 'home', 'color' => 'violeta', 'titulo' => 'Nueva Casa Iglesia', 'texto' => 'Crear una comunidad', 'href' => '/casas/crear'],
            ['icono' => 'calendar', 'color' => 'ambar', 'titulo' => 'Crear evento', 'texto' => 'Organizar un encuentro', 'href' => '/eventos/crear'],
            ['icono' => 'folder', 'color' => 'violeta', 'titulo' => 'Subir material', 'texto' => 'Compartir un recurso', 'href' => '/materiales/crear'],
            ['icono' => 'chart', 'color' => 'verde', 'titulo' => 'Ver informes', 'texto' => 'Revisar la asistencia', 'href' => '/panel/reuniones'],
            ['icono' => 'database', 'color' => 'cian', 'titulo' => 'Ejercicios MongoDB', 'texto' => 'Colección de demostración', 'href' => '/etapa1/sql'],
        ] : ($esLider ? [
            ['icono' => 'calendar', 'color' => 'azul', 'titulo' => 'Registrar reunión', 'texto' => 'Fecha, tema y asistencia', 'href' => '/reuniones/crear'],
            ['icono' => 'check', 'color' => 'verde', 'titulo' => 'Mis reuniones', 'texto' => 'Consultar y editar registros', 'href' => '/reuniones'],
            ['icono' => 'calendar', 'color' => 'ambar', 'titulo' => 'Crear evento', 'texto' => 'Para una Casa a tu cargo', 'href' => '/eventos/crear'],
            ['icono' => 'folder', 'color' => 'violeta', 'titulo' => 'Materiales de estudio', 'texto' => 'Consultar y descargar', 'href' => '/materiales'],
        ] : []);

        $estadisticas = [];
        $recientes = [];
        if ($esAdministrador) {
            $asistencia = $conexion->table('reuniones')->raw(function (Collection $coleccion) {
                return $coleccion->aggregate([
                    ['$project' => [
                        'total' => ['$size' => ['$ifNull' => ['$asistencia', []]]],
                        'presentes' => ['$size' => ['$filter' => [
                            'input' => ['$ifNull' => ['$asistencia', []]],
                            'as' => 'registro',
                            'cond' => ['$eq' => ['$$registro.estado', 'presente']],
                        ]]],
                    ]],
                    ['$group' => ['_id' => null, 'total' => ['$sum' => '$total'], 'presentes' => ['$sum' => '$presentes']]],
                ], ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']])->toArray();
            });
            $totalAsistencia = (int) ($asistencia[0]['total'] ?? 0);
            $porcentaje = $totalAsistencia === 0 ? '—'
                : number_format(100 * (int) $asistencia[0]['presentes'] / $totalAsistencia, 0, ',', '.') . '%';
            $estadisticas = [
                ['icono' => 'users', 'color' => 'azul', 'valor' => $conexion->table('usuarios')->count(), 'titulo' => 'Usuarios', 'nota' => 'Total de personas registradas'],
                ['icono' => 'home', 'color' => 'violeta', 'valor' => $casas->count(), 'titulo' => 'Casas Iglesias', 'nota' => count($idsActivas) . ' activas'],
                ['icono' => 'calendar', 'color' => 'cian', 'valor' => $conexion->table('reuniones')->count(), 'titulo' => 'Reuniones', 'nota' => 'Total de reuniones registradas'],
                ['icono' => 'chart', 'color' => 'verde', 'valor' => $porcentaje, 'titulo' => 'Presencias registradas', 'nota' => 'Sobre presentes, ausentes y pendientes'],
            ];
            $recientes = $conexion->table('reuniones')->select('_id', 'id_casa', 'fecha', 'tema')
                ->orderBy('fecha', 'desc')->orderBy('_id', 'desc')->limit(4)->get()
                ->map(function ($reunion) use ($casasPorId) {
                    $casa = $casasPorId->get((int) $reunion->id_casa);
                    return [
                        'titulo' => $reunion->tema,
                        'casa' => $casa->nombre_casa ?? 'Casa no disponible',
                        'fecha' => $this->fecha($reunion->fecha, 'UTC')->format('d/m/Y'),
                    ];
                })->all();
        }

        $iniciales = Str::of($usuario->nombre)->trim()->explode(' ')
            ->filter()->take(2)->map(fn ($parte) => Str::upper(Str::substr($parte, 0, 1)))->implode('');
        $primerNombre = Str::of($usuario->nombre)->trim()->before(' ')->toString();

        return view('panel.inicio', compact(
            'usuario', 'nombreRol', 'esAdministrador', 'esLider', 'casasPanel',
            'agenda', 'proximaReunion', 'navegacion', 'modulos', 'acciones',
            'estadisticas', 'recientes', 'iniciales', 'primerNombre'
        ));
    }

    private function fecha(DateTimeInterface|UTCDateTime $valor, string $zona): DateTimeImmutable
    {
        $fecha = $valor instanceof UTCDateTime ? $valor->toDateTime() : $valor;
        return DateTimeImmutable::createFromInterface($fecha)->setTimezone(new DateTimeZone($zona));
    }

    private function mes(DateTimeImmutable $fecha): string
    {
        return ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'][(int) $fecha->format('n') - 1];
    }

    private function navegacion(string $rol): array
    {
        $inicio = [['icono' => 'home', 'titulo' => $rol === 'Administrador' ? 'Panel administrativo' : 'Inicio', 'href' => '/panel']];
        return array_merge($inicio, match ($rol) {
            'Administrador' => [
                ['icono' => 'home', 'titulo' => 'Casas Iglesias', 'href' => '/panel/casas'],
                ['icono' => 'users', 'titulo' => 'Usuarios', 'href' => '/panel/usuarios'],
                ['icono' => 'chart', 'titulo' => 'Informes de reuniones', 'href' => '/panel/reuniones'],
                ['icono' => 'calendar', 'titulo' => 'Eventos', 'href' => '/eventos'],
                ['icono' => 'folder', 'titulo' => 'Materiales', 'href' => '/materiales'],
                ['icono' => 'database', 'titulo' => 'Consultas MongoDB', 'href' => '/panel/consultas'],
                ['icono' => 'layers', 'titulo' => 'Ejercicios MongoDB', 'href' => '/etapa1/sql'],
            ],
            'Líder' => [
                ['icono' => 'users', 'titulo' => 'Mis Casas Iglesias', 'href' => '#mis-casas'],
                ['icono' => 'calendar', 'titulo' => 'Mis reuniones', 'href' => '/reuniones'],
                ['icono' => 'check', 'titulo' => 'Registrar reunión', 'href' => '/reuniones/crear'],
                ['icono' => 'calendar', 'titulo' => 'Eventos', 'href' => '/eventos'],
                ['icono' => 'folder', 'titulo' => 'Materiales', 'href' => '/materiales'],
                ['icono' => 'clock', 'titulo' => 'Próximas actividades', 'href' => '#agenda'],
            ],
            default => [
                ['icono' => 'users', 'titulo' => 'Mis Casas Iglesias', 'href' => '#mis-casas'],
                ['icono' => 'calendar', 'titulo' => 'Eventos', 'href' => '/eventos'],
                ['icono' => 'clock', 'titulo' => 'Próximas actividades', 'href' => '#agenda'],
            ],
        });
    }
}
