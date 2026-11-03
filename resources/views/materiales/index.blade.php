@extends('layouts.koinonia')

@section('titulo', 'Materiales de estudio')
@section('icono', 'folder')
@section('tipo', 'listado-ui')
@section('descripcion', 'Recursos para aprender y profundizar juntos en la fe.')

@section('encabezado')
Materiales de estudio
@endsection

@section('contenido')
<nav>
        <a href="/panel">← Volver al panel</a>

        @if ($esAdministrador)
            <a href="/materiales/crear">+ Subir material</a>
        @endif
    </nav>

    

    @if (session('exito'))
        <p class="exito" role="status">{{ session('exito') }}</p>
    @endif

    @if ($errors->any())
        <ul class="error">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form class="filtros" method="GET" action="/materiales">
        <div>
            <label for="categoria">Categoría</label>

            <select id="categoria" name="categoria">
                <option value="">Todas las categorías</option>

                @foreach ($categorias as $categoria)
                    <option
                        value="{{ $categoria }}"
                        @selected($categoriaSeleccionada === $categoria)
                    >
                        {{ $categoria }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit">Filtrar</button>
        <a href="/materiales">Limpiar filtro</a>
    </form>

    @forelse ($materiales as $material)
        <article>
            <h2>{{ $material->titulo }}</h2>

            <p>
                <strong>Categoría:</strong>
                {{ $material->categoria }}
            </p>

            <p>
                <strong>Fecha de subida:</strong>
                {{ $material->fecha_subida }}
            </p>

            @if (! $material->archivo_disponible)
                <p class="aviso">
                    El archivo no está disponible.
                    El administrador debe subirlo o reemplazarlo.
                </p>
            @endif

            <div class="acciones">
                @if ($material->archivo_disponible)
                    <a href="/materiales/{{ $material->id_material }}/descargar">
                        Descargar archivo
                    </a>
                @endif

                @if ($esAdministrador)
                    <a href="/materiales/{{ $material->id_material }}/editar">
                        Editar material
                    </a>

                    <form data-ui-peligro
                        method="POST"
                        action="/materiales/{{ $material->id_material }}"
                        onsubmit="return confirm('¿Quieres eliminar este material y su archivo?');"
                    >
                        @csrf
                        @method('DELETE')

                        <button type="submit" class="eliminar">
                            Eliminar material
                        </button>
                    </form>
                @endif
            </div>
        </article>
    @empty
        <p>No se encontraron materiales con ese filtro.</p>
    @endforelse

    @if ($materiales->hasPages())
        <nav class="paginacion" aria-label="Páginas de materiales">
            @if ($materiales->previousPageUrl())
                <a href="{{ $materiales->previousPageUrl() }}">
                    ← Anterior
                </a>
            @endif

            <span>
                Página {{ $materiales->currentPage() }}
                de {{ $materiales->lastPage() }}
            </span>

            @if ($materiales->nextPageUrl())
                <a href="{{ $materiales->nextPageUrl() }}">
                    Siguiente →
                </a>
            @endif
        </nav>
    @endif
@endsection
