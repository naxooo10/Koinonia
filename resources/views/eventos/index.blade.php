@extends('layouts.koinonia')

@section('titulo', 'Eventos')
@section('icono', 'calendar')
@section('tipo', 'listado-ui')
@section('descripcion', 'Momentos para compartir, encontrarnos y crecer juntos.')

@section('encabezado')
Eventos
@endsection

@section('contenido')
<nav>
        <a href="/panel">← Volver al panel</a>

        @if ($puedeCrear)
            <a href="/eventos/crear">+ Crear evento</a>
        @endif
    </nav>

    

    <p>Fechas y horas expresadas en la zona horaria de Santiago de Chile.</p>

    @if (session('exito'))
        <p class="exito" role="status">{{ session('exito') }}</p>
    @endif

    @forelse ($eventos as $evento)
        <article>
            <h2>{{ $evento->titulo }}</h2>

            <p><strong>Fecha y hora:</strong> {{ $evento->fecha_hora }}</p>
            <p><strong>Alcance:</strong> {{ $evento->nombre_casa }}</p>

            <p class="descripcion">{{ $evento->descripcion }}</p>

            @if ($evento->puede_editar)
                <div class="acciones">
                    <a href="/eventos/{{ $evento->id_evento }}/editar">
                        Editar evento
                    </a>

                    <form data-ui-peligro
                        method="POST"
                        action="/eventos/{{ $evento->id_evento }}"
                        onsubmit="return confirm('¿Quieres eliminar este evento?');"
                    >
                        @csrf
                        @method('DELETE')

                        <button type="submit">Eliminar evento</button>
                    </form>
                </div>
            @endif
        </article>
    @empty
        <p>No hay eventos disponibles para tu cuenta.</p>
    @endforelse

    @if ($eventos->hasPages())
        <nav class="paginacion" aria-label="Páginas de eventos">
            @if ($eventos->previousPageUrl())
                <a href="{{ $eventos->previousPageUrl() }}">← Anterior</a>
            @endif

            <span>
                Página {{ $eventos->currentPage() }}
                de {{ $eventos->lastPage() }}
            </span>

            @if ($eventos->nextPageUrl())
                <a href="{{ $eventos->nextPageUrl() }}">Siguiente →</a>
            @endif
        </nav>
    @endif
@endsection
