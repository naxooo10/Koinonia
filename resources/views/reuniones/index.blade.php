@extends('layouts.koinonia')

@section('titulo', 'Mis reuniones')
@section('icono', 'calendar')
@section('tipo', 'listado-ui')
@section('descripcion', 'Encuentros de las Casas que están bajo tu responsabilidad.')

@section('encabezado')
Mis reuniones
@endsection

@section('contenido')
<nav>
        <a href="/panel">← Volver al panel</a>
        <a href="/reuniones/crear">+ Registrar reunión</a>
    </nav>

    

    <p>
        Reuniones de las Casas activas que están bajo tu responsabilidad.
    </p>

    @if (session('exito'))
        <p class="exito" role="status">{{ session('exito') }}</p>
    @endif

    @if ($reuniones->isEmpty())
        <p>No hay reuniones disponibles para gestionar.</p>
    @else
        <div class="tabla">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Tema</th>
                        <th>Casa Iglesia</th>
                        <th>Presentes</th>
                        <th>Ausentes</th>
                        <th>Pendientes</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($reuniones as $reunion)
                        <tr>
                            <td>{{ $reunion->id_reunion }}</td>
                            <td>{{ $reunion->fecha }}</td>
                            <td>{{ $reunion->tema }}</td>
                            <td>{{ $reunion->nombre_casa }}</td>
                            <td>{{ $reunion->presentes }}</td>
                            <td>{{ $reunion->ausentes }}</td>
                            <td>{{ $reunion->pendientes }}</td>

                            <td>
                                <div class="acciones">
                                    <a href="/reuniones/{{ $reunion->id_reunion }}/editar">
                                        Ver y editar
                                    </a>

                                    <form data-ui-peligro
                                        method="POST"
                                        action="/reuniones/{{ $reunion->id_reunion }}"
                                        onsubmit="return confirm('¿Eliminar esta reunión y toda su asistencia? Esta acción no se puede deshacer.');"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="eliminar"
                                            aria-label="Eliminar reunión {{ $reunion->id_reunion }}"
                                        >
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <nav class="paginacion" aria-label="Páginas de reuniones">
            @if ($reuniones->previousPageUrl())
                <a href="{{ $reuniones->previousPageUrl() }}">
                    ← Anterior
                </a>
            @endif

            <span>
                Página {{ $reuniones->currentPage() }}
                de {{ $reuniones->lastPage() }}
            </span>

            @if ($reuniones->nextPageUrl())
                <a href="{{ $reuniones->nextPageUrl() }}">
                    Siguiente →
                </a>
            @endif
        </nav>
    @endif
@endsection
